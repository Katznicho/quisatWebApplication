<?php

namespace App\Http\Controllers;

use App\Models\Canteen;
use App\Models\CanteenSale;
use App\Services\CanteenReportService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PocketPayController extends Controller
{
    public const REPORTS = [
        'full' => 'Canteen Report',
        'statement' => 'Float Statement',
        'sales' => 'Sales Register',
        'students' => 'Students & Cards',
    ];

    protected const MAX_PDF_ROWS = 3000;

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

    public function cardNumbers()
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);

        return view('pocket-pay.card-numbers', $this->nav());
    }

    public function reversals()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.reversals', $this->nav());
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
