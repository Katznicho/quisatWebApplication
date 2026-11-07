<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenFloatTopup;
use App\Models\CanteenSale;
use App\Exceptions\PocketPayException;
use App\Models\PocketPayCard;
use App\Models\PocketPayGatewaySetting;
use App\Models\PocketPayWithdrawal;
use App\Services\PocketPayService;
use App\Services\PocketPayMoneyReport;
use App\Services\CanteenReportService;
use App\Services\CardReportService;
use App\Services\TableDocumentExporter;
use App\Support\PocketPayAccess;
use App\Support\PocketPayCardQr;
use App\Support\TenantScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PocketPayController extends Controller
{
    public const REPORTS = [
        'full' => 'Canteen Report',
        'statement' => 'Float Statement',
        'sales' => 'Sales Register',
        'students' => 'Students & Cards',
    ];

    protected const MAX_PDF_ROWS = 3000;

    public function overview(Request $request)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $cards = PocketPayCard::query()
            ->selectRaw('business_id, COUNT(*) as cards, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_cards, COALESCE(SUM(balance), 0) as card_balance', [PocketPayCard::STATUS_ACTIVE])
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');

        $floats = Canteen::query()
            ->selectRaw('business_id, COUNT(*) as canteens, COALESCE(SUM(float_balance), 0) as float_balance')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');

        $businessIds = $cards->keys()->merge($floats->keys())->unique()->filter();
        $businesses = Business::query()->whereIn('id', $businessIds)->orderBy('name')->get()->keyBy('id');

        $schools = $businessIds->map(function ($id) use ($cards, $floats, $businesses) {
            $card = $cards->get($id);
            $float = $floats->get($id);
            $cardBalance = (float) ($card->card_balance ?? 0);
            $floatBalance = (float) ($float->float_balance ?? 0);

            return (object) [
                'business' => $businesses->get($id),
                'cards' => (int) ($card->cards ?? 0),
                'active_cards' => (int) ($card->active_cards ?? 0),
                'card_balance' => $cardBalance,
                'canteens' => (int) ($float->canteens ?? 0),
                'float_balance' => $floatBalance,
                'total' => $cardBalance + $floatBalance,
            ];
        })->sortByDesc('total')->values();

        $students = PocketPayCard::query()
            ->with(['student:id,first_name,last_name,student_id', 'business:id,name'])
            ->when($request->filled('school'), fn ($query) => $query->where('business_id', (int) $request->query('school')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('card_number', 'like', $term)
                        ->orWhereHas('student', function ($student) use ($term) {
                            $student->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('student_id', 'like', $term);
                        });
                });
            })
            ->orderByDesc('balance')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('pocket-pay.overview', $this->nav() + [
            'schools' => $schools,
            'students' => $students,
            'cardTotal' => (float) $cards->sum('card_balance'),
            'floatTotal' => (float) $floats->sum('float_balance'),
            'activeCards' => (int) $cards->sum('active_cards'),
            'gateway' => $gateway = PocketPayGatewaySetting::current(),
            'marzBalance' => $this->marzWallet($gateway, $request->boolean('refresh_balance')),
            'schoolFilter' => $businesses,
            'withdrawals' => PocketPayWithdrawal::query()->with('requestedBy:id,name')->latest()->limit(12)->get(),
        ]);
    }

    public function reports(Request $request, PocketPayMoneyReport $reports)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $type = (string) $request->query('report', 'schools');
        abort_unless(array_key_exists($type, PocketPayMoneyReport::REPORTS), 404);

        [$from, $to, $allTime] = $reports->period($request->query('range'), $request->query('from'), $request->query('to'));
        $schoolId = $request->filled('school') ? (int) $request->query('school') : null;

        return view('pocket-pay.reports', $this->nav() + [
            'report' => $type,
            'reports' => PocketPayMoneyReport::REPORTS,
            'summary' => $reports->summary($from, $to, $schoolId),
            'rows' => $reports->paginate($type, $from, $to, $schoolId),
            'headings' => $reports->headings($type),
            'periodLabel' => $reports->periodLabel($from, $to, $allTime),
            'schools' => Business::query()
                ->whereIn('id', PocketPayCard::query()->distinct()->pluck('business_id')
                    ->merge(Canteen::query()->distinct()->pluck('business_id'))
                    ->filter()
                    ->unique())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function downloadReport(Request $request, string $format, PocketPayMoneyReport $reports, TableDocumentExporter $exporter)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $type = (string) $request->query('report', 'everything');
        abort_unless($type === 'everything' || array_key_exists($type, PocketPayMoneyReport::REPORTS), 404);

        [$from, $to, $allTime] = $reports->period($request->query('range'), $request->query('from'), $request->query('to'));
        $schoolId = $request->filled('school') ? (int) $request->query('school') : null;
        $period = $reports->periodLabel($from, $to, $allTime);
        $name = $type === 'everything' ? 'Pocket Pay money' : 'Pocket Pay '.(PocketPayMoneyReport::REPORTS[$type] ?? 'report');

        return $exporter->downloadBundle(
            $format,
            $name.' · '.$period,
            $reports->sections($type, $from, $to, $schoolId),
        );
    }

    public function withdraw(Request $request, PocketPayService $pocketPay)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:500|max:5000000',
            'phone_number' => 'required|string|max:20',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $pocketPay->withdrawToPhone($request->user(), (float) $data['amount'], $data['phone_number'], $data['notes'] ?? null);
        } catch (PocketPayException $e) {
            return back()->withErrors(['phone_number' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Withdrawal sent from the Pocket Pay Marz Pay account.');
    }

    public function withdrawToBank(Request $request, PocketPayService $pocketPay)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:500|max:5000000',
            'bank_name' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:40',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_branch' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $pocketPay->withdrawToBank(
                $request->user(),
                (float) $data['amount'],
                $data['bank_name'],
                $data['bank_account_number'],
                $data['bank_account_name'] ?? null,
                $data['bank_branch'] ?? null,
                $data['notes'] ?? null,
            );
        } catch (PocketPayException $e) {
            return back()->withErrors(['bank_account_number' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Bank withdrawal sent from the Pocket Pay Marz Pay account.');
    }

    public function editSettings(Request $request)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $settings = PocketPayGatewaySetting::current();

        return view('pocket-pay.settings', $this->nav() + [
            'settings' => $settings,
            'marzBalance' => $this->marzWallet($settings, $request->boolean('refresh_balance')),
        ]);
    }

    public function updateSettings(Request $request)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        $data = $request->validate([
            'marzpay_base_url' => 'nullable|url|max:255',
            'marzpay_api_key' => 'nullable|string|max:4000',
            'marzpay_api_secret' => 'nullable|string|max:4000',
            'marzpay_webhook_secret' => 'nullable|string|max:4000',
            'clear_marzpay' => 'nullable|boolean',
            'clear_webhook_secret' => 'nullable|boolean',
        ]);

        $settings = PocketPayGatewaySetting::current();
        $payload = [
            'marzpay_base_url' => trim((string) ($data['marzpay_base_url'] ?? '')) ?: config('marzpay.base_url'),
        ];

        if ($request->boolean('clear_marzpay')) {
            $payload['marzpay_api_key'] = null;
            $payload['marzpay_api_secret'] = null;
        } else {
            $apiKey = trim((string) ($data['marzpay_api_key'] ?? ''));
            $apiSecret = trim((string) ($data['marzpay_api_secret'] ?? ''));
            if ($apiKey !== '') {
                $payload['marzpay_api_key'] = $apiKey;
            }
            if ($apiSecret !== '') {
                $payload['marzpay_api_secret'] = $apiSecret;
            }
            if ($apiKey !== '' && $apiSecret === '' && ! $settings->marzpay_api_secret) {
                return back()->withErrors(['marzpay_api_secret' => 'Enter the API secret with the API key.'])->withInput();
            }
            if ($apiSecret !== '' && $apiKey === '' && ! $settings->marzpay_api_key) {
                return back()->withErrors(['marzpay_api_key' => 'Enter the API key with the API secret.'])->withInput();
            }
        }

        if ($request->boolean('clear_webhook_secret')) {
            $payload['marzpay_webhook_secret'] = null;
        } else {
            $webhookSecret = trim((string) ($data['marzpay_webhook_secret'] ?? ''));
            if ($webhookSecret !== '') {
                $payload['marzpay_webhook_secret'] = $webhookSecret;
            }
        }

        $settings->update($payload);

        return redirect()->route('pocket-pay.settings.edit')->with('success', 'Pocket Pay Marz Pay account saved.');
    }

    public function canteens()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.canteens', $this->nav());
    }

    public function showCanteen(Canteen $canteen)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($canteen);

        return view('pocket-pay.canteen', $this->nav() + ['canteen' => $canteen]);
    }

    public function floatTopupProof(CanteenFloatTopup $topup)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($topup);

        $disk = Storage::disk(CanteenFloatTopup::PROOF_DISK);
        abort_unless($disk->exists($topup->proof_path), 404);

        return $disk->response($topup->proof_path, $topup->proof_name ?: basename($topup->proof_path));
    }

    public function canteenReport(Request $request, Canteen $canteen, string $type, CanteenReportService $reports)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($canteen);
        abort_unless(array_key_exists($type, self::REPORTS), 404);

        [$from, $to] = $reports->period($canteen, $request->query('from'), $request->query('to'));
        [$start, $end] = $reports->bounds($from, $to);
        $report = $reports->build($canteen, $from, $to);

        $sales = in_array($type, ['full', 'sales'], true)
            ? $reports->salesQuery($canteen, $start, $end)->orderBy('id')->limit(self::MAX_PDF_ROWS + 1)->get()
            : collect();
        $cardholders = in_array($type, ['full', 'students'], true)
            ? $reports->cardholdersQuery($canteen, $start, $end)->orderByDesc('spent_here')->orderBy('id')->limit(self::MAX_PDF_ROWS + 1)->get()
            : collect();

        $pdf = Pdf::loadView('pocket-pay.reports.canteen-pdf', [
            'report' => $report,
            'type' => $type,
            'title' => self::REPORTS[$type],
            'sales' => $sales->take(self::MAX_PDF_ROWS),
            'salesTruncated' => $sales->count() > self::MAX_PDF_ROWS,
            'cardholders' => $cardholders->take(self::MAX_PDF_ROWS),
            'cardholdersTruncated' => $cardholders->count() > self::MAX_PDF_ROWS,
            'generatedBy' => Auth::user()?->name,
        ])
            ->setPaper('a4', $type === 'students' ? 'portrait' : 'landscape');

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_text($canvas->get_width() - 100, $canvas->get_height() - 25, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 7.5, [0.42, 0.45, 0.5]);

        return $pdf->stream($reports->filename($canteen, $type, $from, $to));
    }

    public function cards()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.cards', $this->nav());
    }

    public function showCard(PocketPayCard $card)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($card);

        return view('pocket-pay.card', $this->nav() + ['card' => $card]);
    }

    public function cardStatement(Request $request, PocketPayCard $card, CardReportService $reports)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($card);

        [$from, $to] = $reports->period($card, $request->query('from'), $request->query('to'));
        [$start, $end] = $reports->bounds($from, $to);
        $report = $reports->build($card, $from, $to);
        $sales = $reports->salesQuery($card, $start, $end)->orderBy('id')->limit(self::MAX_PDF_ROWS + 1)->get();

        $pdf = Pdf::loadView('pocket-pay.reports.card-statement-pdf', [
            'report' => $report,
            'school' => app(CanteenReportService::class)->schoolDetails($card->business),
            'sales' => $sales->take(self::MAX_PDF_ROWS),
            'salesTruncated' => $sales->count() > self::MAX_PDF_ROWS,
            'generatedBy' => Auth::user()?->name,
        ])->setPaper('a4', 'portrait');

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_text($canvas->get_width() - 100, $canvas->get_height() - 25, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 7.5, [0.42, 0.45, 0.5]);

        return $pdf->stream($reports->filename($card, $from, $to));
    }

    public function cardQr(PocketPayCard $card)
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        return response()->streamDownload(
            fn () => print (PocketPayCardQr::svg($card->card_number)),
            "card-{$card->card_number}.svg",
            ['Content-Type' => 'image/svg+xml'],
        );
    }

    public function cardNumbers()
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        return view('pocket-pay.card-numbers', $this->nav());
    }

    public function reversals()
    {
        PocketPayAccess::authorize();
        abort_unless(PocketPayAccess::canReviewReversals(), 403);

        return view('pocket-pay.reversals', $this->nav());
    }

    protected function marzWallet(PocketPayGatewaySetting $settings, bool $fresh): ?array
    {
        if (! $settings->hasPayCredentials()) {
            return null;
        }

        return app(\App\Services\MarzPayService::class)->forPocketPay()->walletBalance($fresh);
    }

    protected function nav(): array
    {
        return [
            'pendingReversals' => TenantScope::apply(CanteenSale::query())
                ->where('reversal_status', CanteenSale::REVERSAL_PENDING)
                ->count(),
        ];
    }
}
