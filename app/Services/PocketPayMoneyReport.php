<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenFloatTopup;
use App\Models\CanteenFloatTransaction;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardNumber;
use App\Models\PocketPayCardTransaction;
use App\Models\PocketPayTopup;
use App\Models\PocketPayWithdrawal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin money view for Pocket Pay: current balances plus every movement.
 */
class PocketPayMoneyReport
{
    public const REPORTS = [
        'schools' => 'Schools',
        'students' => 'Student cards',
        'canteens' => 'Canteen floats',
        'sales' => 'Sales',
        'topups' => 'Mobile money top-ups',
        'float-topups' => 'Float deposits',
        'card-ledger' => 'Card movements',
        'float-ledger' => 'Float movements',
        'reversals' => 'Reversals',
        'withdrawals' => 'Withdrawals',
    ];

    public const MAX_ROWS = 15000;

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: bool}
     */
    public function period(?string $range, ?string $from, ?string $to): array
    {
        $tz = (string) config('app.timezone', 'Africa/Nairobi');
        $today = CarbonImmutable::now($tz)->startOfDay();

        if (filled($from) || filled($to)) {
            $start = $this->parseDate($from, $tz) ?? $today->startOfMonth();
            $end = $this->parseDate($to, $tz) ?? $today;
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
            if ($start->diffInDays($end) > 1825) {
                $start = $end->subDays(1825);
            }

            return [$start, $end, false];
        }

        return match ($range) {
            'year' => [$today->startOfYear(), $today, false],
            'all' => [null, null, true],
            default => [$today->startOfMonth(), $today, false],
        };
    }

    public function periodLabel(?CarbonImmutable $from, ?CarbonImmutable $to, bool $allTime): string
    {
        if ($allTime || ! $from || ! $to) {
            return 'All time';
        }

        return $from->format('j M Y').' – '.$to->format('j M Y');
    }

    public function summary(?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): array
    {
        $cards = PocketPayCard::query()->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));
        $canteens = Canteen::query()->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));
        $cardTotal = (float) (clone $cards)->sum('balance');
        $floatTotal = (float) (clone $canteens)->sum('float_balance');

        $sales = $this->dated(CanteenSale::query(), 'created_at', $from, $to)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));
        $topups = $this->dated(PocketPayTopup::query(), 'created_at', $from, $to)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));
        $deposits = $this->dated(CanteenFloatTopup::query(), 'paid_on', $from, $to, true)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));
        $withdrawals = $this->dated(PocketPayWithdrawal::query(), 'created_at', $from, $to);

        return [
            'card_balance' => $cardTotal,
            'float_balance' => $floatTotal,
            'held' => $cardTotal + $floatTotal,
            'active_cards' => (int) (clone $cards)->where('status', PocketPayCard::STATUS_ACTIVE)->count(),
            'sales' => (float) (clone $sales)->where('status', CanteenSale::STATUS_COMPLETED)->sum('amount'),
            'reversed' => (float) (clone $sales)->where('status', CanteenSale::STATUS_REVERSED)->sum('amount'),
            'topups' => (float) (clone $topups)->where('status', 'completed')->sum('amount'),
            'topup_charges' => $this->topupCharges($from, $to, $schoolId),
            'deposits' => (float) (clone $deposits)->sum('amount'),
            'withdrawals' => (float) (clone $withdrawals)->where('status', 'completed')->sum('amount'),
            'pending_reversals' => (int) CanteenSale::query()
                ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
                ->where('reversal_status', CanteenSale::REVERSAL_PENDING)
                ->count(),
        ];
    }

    public function paginate(string $type, ?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): LengthAwarePaginator
    {
        if ($type === 'schools') {
            $rows = collect($this->schoolRows($from, $to, $schoolId));
            $page = Paginator::resolveCurrentPage();
            $perPage = 25;

            return new Paginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $paginator = $this->query($type, $from, $to, $schoolId)
            ->paginate(25)
            ->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map(fn ($model) => $this->map($type, $model)));

        return $paginator;
    }

    /**
     * @return array<int, array{title: string, headings: array<int, string>, rows: array<int, array<int, string>>}>
     */
    public function sections(string $type, ?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): array
    {
        $types = $type === 'everything' ? array_keys(self::REPORTS) : [$type];
        $sections = [];
        if ($type === 'everything') {
            $sections[] = $this->summarySection($from, $to, $schoolId);
        }

        foreach ($types as $report) {
            $sections[] = [
                'title' => self::REPORTS[$report],
                'headings' => $this->headings($report),
                'rows' => $this->exportRows($report, $from, $to, $schoolId),
            ];
        }

        return $sections;
    }

    public function headings(string $type): array
    {
        return match ($type) {
            'schools' => ['School', 'Cards', 'Active cards', 'On cards (UGX)', 'Canteens', 'Float (UGX)', 'Total held (UGX)', 'Sales (UGX)', 'Reversed (UGX)', 'Mobile money (UGX)', 'Float deposits (UGX)'],
            'students' => ['Student', 'Student ID', 'School', 'Card', 'Status', 'Balance (UGX)', 'Daily limit (UGX)'],
            'canteens' => ['School', 'Canteen', 'Location', 'Status', 'Float (UGX)', 'Low float (UGX)'],
            'sales' => ['Date', 'Receipt', 'School', 'Canteen', 'Student', 'Card', 'Attendant', 'Amount (UGX)', 'Status', 'Reversal', 'Description'],
            'topups' => ['Date', 'School', 'Paid into', 'Phone', 'Amount (UGX)', 'Charge (UGX)', 'Status', 'Credited', 'Marz reference'],
            'float-topups' => ['Paid on', 'School', 'Canteen', 'Amount (UGX)', 'Reference', 'Note', 'Recorded by'],
            'card-ledger' => ['Date', 'School', 'Student', 'Card', 'Canteen', 'Type', 'Direction', 'Amount (UGX)', 'Balance after (UGX)', 'Description'],
            'float-ledger' => ['Date', 'School', 'Canteen', 'Type', 'Direction', 'Amount (UGX)', 'Balance after (UGX)', 'Description'],
            'reversals' => ['Requested', 'Receipt', 'School', 'Canteen', 'Student', 'Amount (UGX)', 'Sale status', 'Reversal', 'Reason', 'Reviewed', 'Reviewer', 'Review note'],
            'withdrawals' => ['Date', 'Method', 'Destination', 'Amount (UGX)', 'Status', 'Marz reference', 'Provider reference', 'Requested by', 'Notes'],
            default => [],
        };
    }

    private function summarySection(?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): array
    {
        $summary = $this->summary($from, $to, $schoolId);
        $rows = [
            ['On student cards now', $this->amount($summary['card_balance'])],
            ['In canteen floats now', $this->amount($summary['float_balance'])],
            ['Total held now', $this->amount($summary['held'])],
            ['Active cards', (string) $summary['active_cards']],
            ['Sales completed', $this->amount($summary['sales'])],
            ['Sales reversed', $this->amount($summary['reversed'])],
            ['Mobile money completed', $this->amount($summary['topups'])],
            ['Mobile money charges', $this->amount($summary['topup_charges'])],
            ['Float deposits', $this->amount($summary['deposits'])],
            ['Withdrawals completed', $this->amount($summary['withdrawals'])],
            ['Reversals waiting', (string) $summary['pending_reversals']],
        ];

        return [
            'title' => 'Summary',
            'headings' => ['Item', 'Amount (UGX)'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function exportRows(string $type, ?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): array
    {
        if ($type === 'schools') {
            return array_map(fn (array $row) => array_values($row), $this->schoolRows($from, $to, $schoolId));
        }

        $models = $this->query($type, $from, $to, $schoolId)->limit(self::MAX_ROWS + 1)->get();
        $truncated = $models->count() > self::MAX_ROWS;
        $rows = $models->take(self::MAX_ROWS)
            ->map(fn ($model) => array_values($this->map($type, $model)))
            ->all();

        if ($truncated) {
            $rows[] = array_merge(
                ['Stopped after '.number_format(self::MAX_ROWS).' rows. Narrow the dates to export the rest.'],
                array_fill(0, max(count($this->headings($type)) - 1, 0), '')
            );
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function schoolRows(?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): array
    {
        $cards = PocketPayCard::query()
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
            ->selectRaw('business_id, COUNT(*) as cards, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_cards, COALESCE(SUM(balance), 0) as card_balance', [PocketPayCard::STATUS_ACTIVE])
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');
        $floats = Canteen::query()
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
            ->selectRaw('business_id, COUNT(*) as canteens, COALESCE(SUM(float_balance), 0) as float_balance')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');
        $sales = $this->dated(CanteenSale::query(), 'created_at', $from, $to)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
            ->selectRaw('business_id, status, COALESCE(SUM(amount), 0) as total')
            ->groupBy('business_id', 'status')
            ->get();
        $topups = $this->dated(PocketPayTopup::query()->where('status', 'completed'), 'created_at', $from, $to)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
            ->selectRaw('business_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');
        $deposits = $this->dated(CanteenFloatTopup::query(), 'paid_on', $from, $to, true)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId))
            ->selectRaw('business_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');

        $ids = $cards->keys()->merge($floats->keys())->merge($sales->pluck('business_id'))->merge($topups->keys())->merge($deposits->keys())->unique()->filter();
        $businesses = Business::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(function ($id) use ($cards, $floats, $sales, $topups, $deposits, $businesses) {
            $card = $cards->get($id);
            $float = $floats->get($id);
            $cardBalance = (float) ($card->card_balance ?? 0);
            $floatBalance = (float) ($float->float_balance ?? 0);
            $schoolSales = $sales->where('business_id', $id);

            return [
                'School' => $businesses->get($id)?->name ?? 'School',
                'Cards' => (string) (int) ($card->cards ?? 0),
                'Active cards' => (string) (int) ($card->active_cards ?? 0),
                'On cards (UGX)' => $this->amount($cardBalance),
                'Canteens' => (string) (int) ($float->canteens ?? 0),
                'Float (UGX)' => $this->amount($floatBalance),
                'Total held (UGX)' => $this->amount($cardBalance + $floatBalance),
                'Sales (UGX)' => $this->amount($schoolSales->firstWhere('status', CanteenSale::STATUS_COMPLETED)->total ?? 0),
                'Reversed (UGX)' => $this->amount($schoolSales->firstWhere('status', CanteenSale::STATUS_REVERSED)->total ?? 0),
                'Mobile money (UGX)' => $this->amount($topups->get($id)->total ?? 0),
                'Float deposits (UGX)' => $this->amount($deposits->get($id)->total ?? 0),
            ];
        })->sortByDesc(fn (array $row) => (float) $row['Total held (UGX)'])->values()->all();
    }

    private function query(string $type, ?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): Builder
    {
        $school = fn (Builder $query) => $query->when($schoolId, fn ($inner) => $inner->where('business_id', $schoolId));

        return match ($type) {
            'students' => $school(PocketPayCard::query()->with(['student:id,first_name,last_name,student_id', 'business:id,name']))
                ->orderByDesc('balance')->orderBy('id'),
            'canteens' => $school(Canteen::query()->with('business:id,name'))
                ->orderByDesc('float_balance')->orderBy('id'),
            'sales' => $school(CanteenSale::query()->with(['business:id,name', 'canteen:id,name', 'student:id,first_name,last_name', 'card:id,card_number', 'attendant:id,name']))
                ->tap(fn (Builder $query) => $this->dated($query, 'created_at', $from, $to))
                ->orderByDesc('id'),
            'topups' => $school(PocketPayTopup::query()->with([
                'business:id,name',
                'paymentCollections',
                'topupable' => function (MorphTo $morph) {
                    $morph->morphWith([
                        PocketPayCard::class => ['student:id,first_name,last_name'],
                        Canteen::class => [],
                    ]);
                },
            ]))
                ->tap(fn (Builder $query) => $this->dated($query, 'created_at', $from, $to))
                ->orderByDesc('id'),
            'float-topups' => $school(CanteenFloatTopup::query()->with(['business:id,name', 'canteen:id,name', 'recorder:id,name']))
                ->tap(fn (Builder $query) => $this->dated($query, 'paid_on', $from, $to, true))
                ->orderByDesc('id'),
            'card-ledger' => $school(PocketPayCardTransaction::query()->with(['card.student:id,first_name,last_name', 'card.business:id,name', 'canteen:id,name']))
                ->tap(fn (Builder $query) => $this->dated($query, 'created_at', $from, $to))
                ->orderByDesc('id'),
            'float-ledger' => $school(CanteenFloatTransaction::query()->with(['canteen.business:id,name']))
                ->tap(fn (Builder $query) => $this->dated($query, 'created_at', $from, $to))
                ->orderByDesc('id'),
            'reversals' => $school(CanteenSale::query()->with(['business:id,name', 'canteen:id,name', 'student:id,first_name,last_name', 'reviewer:id,name']))
                ->whereNotNull('reversal_status')
                ->tap(fn (Builder $query) => $this->dated($query, 'reversal_requested_at', $from, $to))
                ->orderByDesc('id'),
            'withdrawals' => PocketPayWithdrawal::query()->with('requestedBy:id,name')
                ->tap(fn (Builder $query) => $this->dated($query, 'created_at', $from, $to))
                ->orderByDesc('id'),
            default => PocketPayCard::query()->whereRaw('0 = 1'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function map(string $type, mixed $model): array
    {
        return match ($type) {
            'students' => [
                'Student' => $model->student?->full_name ?? 'Student',
                'Student ID' => (string) ($model->student?->student_id ?? ''),
                'School' => (string) ($model->business?->name ?? ''),
                'Card' => PocketPayCardNumber::format($model->card_number),
                'Status' => ucfirst((string) $model->status),
                'Balance (UGX)' => $this->amount($model->balance),
                'Daily limit (UGX)' => $model->daily_limit === null ? '' : $this->amount($model->daily_limit),
            ],
            'canteens' => [
                'School' => (string) ($model->business?->name ?? ''),
                'Canteen' => (string) $model->name,
                'Location' => (string) ($model->location ?? ''),
                'Status' => ucfirst((string) $model->status),
                'Float (UGX)' => $this->amount($model->float_balance),
                'Low float (UGX)' => $this->amount($model->low_float_threshold),
            ],
            'sales' => [
                'Date' => $this->when($model->created_at),
                'Receipt' => (string) $model->receipt_number,
                'School' => (string) ($model->business?->name ?? ''),
                'Canteen' => (string) ($model->canteen?->name ?? ''),
                'Student' => $model->student?->full_name ?? '',
                'Card' => PocketPayCardNumber::format($model->card?->card_number),
                'Attendant' => (string) ($model->attendant?->name ?? ''),
                'Amount (UGX)' => $this->amount($model->amount),
                'Status' => ucfirst((string) $model->status),
                'Reversal' => $model->reversal_status ? ucfirst((string) $model->reversal_status) : '',
                'Description' => (string) ($model->description ?? ''),
            ],
            'topups' => $this->topupRow($model),
            'float-topups' => [
                'Paid on' => optional($model->paid_on)->format('Y-m-d') ?? '',
                'School' => (string) ($model->business?->name ?? ''),
                'Canteen' => (string) ($model->canteen?->name ?? ''),
                'Amount (UGX)' => $this->amount($model->amount),
                'Reference' => (string) ($model->payment_reference ?? ''),
                'Note' => (string) ($model->note ?? ''),
                'Recorded by' => (string) ($model->recorder?->name ?? ''),
            ],
            'card-ledger' => [
                'Date' => $this->when($model->created_at),
                'School' => (string) ($model->card?->business?->name ?? ''),
                'Student' => $model->card?->student?->full_name ?? '',
                'Card' => PocketPayCardNumber::format($model->card?->card_number),
                'Canteen' => (string) ($model->canteen?->name ?? ''),
                'Type' => ucfirst((string) $model->type),
                'Direction' => ucfirst((string) $model->direction),
                'Amount (UGX)' => $this->amount($model->amount),
                'Balance after (UGX)' => $this->amount($model->balance_after),
                'Description' => (string) ($model->description ?? ''),
            ],
            'float-ledger' => [
                'Date' => $this->when($model->created_at),
                'School' => (string) ($model->canteen?->business?->name ?? ''),
                'Canteen' => (string) ($model->canteen?->name ?? ''),
                'Type' => ucfirst((string) $model->type),
                'Direction' => ucfirst((string) $model->direction),
                'Amount (UGX)' => $this->amount($model->amount),
                'Balance after (UGX)' => $this->amount($model->balance_after),
                'Description' => (string) ($model->description ?? ''),
            ],
            'reversals' => [
                'Requested' => $this->when($model->reversal_requested_at),
                'Receipt' => (string) $model->receipt_number,
                'School' => (string) ($model->business?->name ?? ''),
                'Canteen' => (string) ($model->canteen?->name ?? ''),
                'Student' => $model->student?->full_name ?? '',
                'Amount (UGX)' => $this->amount($model->amount),
                'Sale status' => ucfirst((string) $model->status),
                'Reversal' => ucfirst((string) $model->reversal_status),
                'Reason' => (string) ($model->reversal_reason ?? ''),
                'Reviewed' => $this->when($model->reversal_reviewed_at),
                'Reviewer' => (string) ($model->reviewer?->name ?? ''),
                'Review note' => (string) ($model->reversal_review_note ?? ''),
            ],
            'withdrawals' => [
                'Date' => $this->when($model->created_at),
                'Method' => $model->method === PocketPayWithdrawal::METHOD_BANK ? 'Bank' : 'Mobile money',
                'Destination' => $model->method === PocketPayWithdrawal::METHOD_BANK
                    ? trim($model->bank_name.' '.$model->bank_account_number)
                    : (string) $model->phone_number,
                'Amount (UGX)' => $this->amount($model->amount),
                'Status' => ucfirst((string) $model->status),
                'Marz reference' => (string) ($model->marz_transaction_uuid ?? ''),
                'Provider reference' => (string) ($model->provider_reference ?? ''),
                'Requested by' => (string) ($model->requestedBy?->name ?? ''),
                'Notes' => (string) ($model->notes ?? ''),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function topupRow(PocketPayTopup $topup): array
    {
        $target = $topup->topupable;
        $paidInto = match (true) {
            $target instanceof PocketPayCard => 'Student card · '.($target->student?->full_name ?? PocketPayCardNumber::format($target->card_number)),
            $target instanceof Canteen => 'Canteen float · '.$target->name,
            default => 'Pocket Pay',
        };
        $collection = $topup->paymentCollections->sortByDesc('id')->first();

        return [
            'Date' => $this->when($topup->created_at),
            'School' => (string) ($topup->business?->name ?? ''),
            'Paid into' => $paidInto,
            'Phone' => (string) $topup->phone_number,
            'Amount (UGX)' => $this->amount($topup->amount),
            'Charge (UGX)' => $this->amount($collection->platform_charge ?? 0),
            'Status' => ucfirst((string) $topup->status),
            'Credited' => $this->when($topup->credited_at),
            'Marz reference' => (string) ($collection->reference ?? ''),
        ];
    }

    private function topupCharges(?CarbonImmutable $from, ?CarbonImmutable $to, ?int $schoolId): float
    {
        $latest = DB::table('payment_collections')
            ->selectRaw('MAX(id) as id')
            ->where('payable_type', PocketPayTopup::class)
            ->groupBy('payable_id');

        $topups = $this->dated(PocketPayTopup::query()->where('status', 'completed'), 'created_at', $from, $to)
            ->when($schoolId, fn ($query) => $query->where('business_id', $schoolId));

        return (float) DB::query()
            ->fromSub($latest, 'latest')
            ->join('payment_collections', 'payment_collections.id', '=', 'latest.id')
            ->whereIn('payment_collections.payable_id', $topups->select('pocket_pay_topups.id'))
            ->sum('payment_collections.platform_charge');
    }

    private function dated(Builder $query, string $column, ?CarbonImmutable $from, ?CarbonImmutable $to, bool $dateOnly = false): Builder
    {
        if (! $from || ! $to) {
            return $query;
        }

        if ($dateOnly) {
            return $query->whereBetween($column, [$from->toDateString(), $to->toDateString()]);
        }

        return $query->whereBetween($column, [
            $from->startOfDay()->setTimezone((string) config('app.timezone')),
            $to->endOfDay()->setTimezone((string) config('app.timezone')),
        ]);
    }

    private function amount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function when(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        return $value->timezone((string) config('app.timezone', 'Africa/Nairobi'))->format('Y-m-d H:i');
    }

    private function parseDate(?string $value, string $tz): ?CarbonImmutable
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, $tz)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
