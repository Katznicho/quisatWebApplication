<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenFloatTransaction;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures for the canteen detail page and its PDF reports. Periods are whole days in the
 * school's timezone; every total covers that period unless named "all time".
 */
class CanteenReportService
{
    public const MAX_DAYS = 366;

    /**
     * Resolve a from/to pair (Y-m-d, school-local) into a valid period, defaulting to this month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(Canteen $canteen, ?string $from, ?string $to): array
    {
        return $this->periodIn($this->timezone($canteen), $from, $to);
    }

    /**
     * Same as period() for any school timezone; the card detail page uses it too.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodIn(string $tz, ?string $from, ?string $to): array
    {
        $today = CarbonImmutable::now($tz)->startOfDay();

        $start = $this->parseDate($from, $tz) ?? $today->startOfMonth();
        $end = $this->parseDate($to, $tz) ?? $today;

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            $start = $end->subDays(self::MAX_DAYS - 1);
        }

        return [$start, $end];
    }

    /**
     * UTC-equivalent bounds (app timezone) for a school-local period, for querying created_at.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [
            $from->startOfDay()->setTimezone(config('app.timezone')),
            $to->endOfDay()->setTimezone(config('app.timezone')),
        ];
    }

    public function build(Canteen $canteen, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $canteen->loadMissing('business');
        $tz = $this->timezone($canteen);
        [$start, $end] = $this->bounds($from, $to);

        $sales = CanteenSale::query()
            ->where('canteen_id', $canteen->id)
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'amount', 'status', 'canteen_attendant_id', 'student_id', 'created_at']);

        $completed = $sales->where('status', CanteenSale::STATUS_COMPLETED);
        $reversed = $sales->where('status', CanteenSale::STATUS_REVERSED);
        $netSales = (float) $completed->sum('amount');

        $ledger = $this->ledgerQuery($canteen, $start, $end)->get();
        $opening = $this->balanceBefore($canteen, $start);
        $closing = $ledger->isNotEmpty() ? (float) $ledger->last()->balance_after : $opening;

        $pendingReversals = CanteenSale::query()
            ->where('canteen_id', $canteen->id)
            ->where('reversal_status', CanteenSale::REVERSAL_PENDING);

        return [
            'canteen' => $canteen,
            'school' => $this->school($canteen),
            'from' => $from,
            'to' => $to,
            'timezone' => $tz,
            'generated_at' => CarbonImmutable::now($tz),
            'summary' => [
                'net_sales' => $netSales,
                'gross_sales' => (float) $sales->sum('amount'),
                'transactions' => $completed->count(),
                'average_sale' => $completed->count() > 0 ? $netSales / $completed->count() : 0.0,
                'students_served' => $completed->pluck('student_id')->filter()->unique()->count(),
                'reversed_count' => $reversed->count(),
                'reversed_amount' => (float) $reversed->sum('amount'),
                'float_opening' => $opening,
                'float_closing' => $closing,
                'float_topups' => (float) $ledger->where('type', 'topup')->sum('amount'),
                'float_credits' => (float) $ledger->where('direction', 'credit')->sum('amount'),
                'float_debits' => (float) $ledger->where('direction', 'debit')->sum('amount'),
                'float_balance' => (float) $canteen->float_balance,
                'pending_reversals' => (clone $pendingReversals)->count(),
                'pending_reversals_amount' => (float) (clone $pendingReversals)->sum('amount'),
                'pending_topups' => (float) $canteen->topups()->whereNull('credited_at')->whereIn('status', ['pending', 'processing'])->sum('amount'),
                'attendants' => $canteen->attendants()->count(),
                'active_attendants' => $canteen->attendants()->where('status', CanteenAttendant::STATUS_ACTIVE)->count(),
                'cardholders' => PocketPayCard::query()->where('business_id', $canteen->business_id)->count(),
                'active_cards' => PocketPayCard::query()->where('business_id', $canteen->business_id)->where('status', PocketPayCard::STATUS_ACTIVE)->count(),
            ],
            'daily' => $this->daily($completed, $from, $to, $tz),
            'hourly' => $this->hourly($completed, $tz),
            'attendants' => $this->attendants($canteen, $sales),
            'top_students' => $this->topStudents($completed),
            'ledger' => $ledger,
        ];
    }

    public function salesQuery(Canteen $canteen, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return CanteenSale::query()
            ->where('canteen_id', $canteen->id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['student:id,first_name,last_name,class_room_id', 'student.classRoom:id,name', 'card:id,card_number', 'attendant:id,name', 'reviewer:id,name']);
    }

    public function ledgerQuery(Canteen $canteen, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return CanteenFloatTransaction::query()
            ->where('canteen_id', $canteen->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('reference')
            ->orderBy('id');
    }

    /**
     * Every card at the canteen's school, with what each student spent at this canteen in the period.
     */
    public function cardholdersQuery(Canteen $canteen, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        $here = fn ($q) => $q->where('canteen_id', $canteen->id)
            ->where('status', CanteenSale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end]);

        return PocketPayCard::query()
            ->where('business_id', $canteen->business_id)
            ->with(['student:id,first_name,last_name,student_id,class_room_id', 'student.classRoom:id,name'])
            ->withSum(['sales as spent_here' => $here], 'amount')
            ->withCount(['sales as purchases_here' => $here])
            ->withMax(['sales as last_purchase_here' => fn ($q) => $q->where('canteen_id', $canteen->id)->where('status', CanteenSale::STATUS_COMPLETED)], 'created_at');
    }

    public function balanceBefore(Canteen $canteen, CarbonImmutable $start): float
    {
        return (float) (CanteenFloatTransaction::query()
            ->where('canteen_id', $canteen->id)
            ->where('created_at', '<', $start)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }

    public function timezone(Canteen $canteen): string
    {
        return $canteen->business?->timezoneName() ?? config('app.timezone');
    }

    public function filename(Canteen $canteen, string $type, CarbonImmutable $from, CarbonImmutable $to): string
    {
        return str($canteen->name)->slug().'-'.$type.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';
    }

    protected function school(Canteen $canteen): array
    {
        return $this->schoolDetails($canteen->business);
    }

    /**
     * Name, contacts and logo of a school for page headers and PDFs.
     */
    public function schoolDetails(?Business $business): array
    {
        $logo = $business?->logo && $business->logo !== 'logo.png' ? $business->logo : null;
        $logoPath = $logo ? public_path('storage/'.$logo) : null;

        return [
            'name' => $business?->name,
            'address' => $business?->address,
            'phone' => $business?->phone,
            'email' => $business?->email,
            'logo_url' => $logo ? asset('storage/'.$logo) : null,
            'logo_path' => $logoPath && is_file($logoPath) ? $logoPath : null,
        ];
    }

    /**
     * @return list<array{date: CarbonImmutable, count: int, amount: float}>
     */
    protected function daily(Collection $completed, CarbonImmutable $from, CarbonImmutable $to, string $tz): array
    {
        $byDay = $completed->groupBy(fn (CanteenSale $sale) => $sale->created_at->copy()->setTimezone($tz)->toDateString());
        $days = [];

        for ($day = $from->startOfDay(); $day->lte($to); $day = $day->addDay()) {
            $rows = $byDay->get($day->toDateString(), collect());
            $days[] = ['date' => $day, 'count' => $rows->count(), 'amount' => (float) $rows->sum('amount')];
        }

        return $days;
    }

    /**
     * @return array<int, array{count: int, amount: float}>
     */
    protected function hourly(Collection $completed, string $tz): array
    {
        $byHour = $completed->groupBy(fn (CanteenSale $sale) => (int) $sale->created_at->copy()->setTimezone($tz)->format('G'));

        return collect(range(0, 23))
            ->mapWithKeys(fn (int $hour) => [$hour => [
                'count' => $byHour->get($hour, collect())->count(),
                'amount' => (float) $byHour->get($hour, collect())->sum('amount'),
            ]])
            ->all();
    }

    protected function attendants(Canteen $canteen, Collection $sales): Collection
    {
        $byAttendant = $sales->groupBy('canteen_attendant_id');

        return CanteenAttendant::withTrashed()
            ->where('canteen_id', $canteen->id)
            ->orderBy('name')
            ->get()
            ->map(function (CanteenAttendant $attendant) use ($byAttendant) {
                $rows = $byAttendant->get($attendant->id, collect());
                $completed = $rows->where('status', CanteenSale::STATUS_COMPLETED);

                return [
                    'attendant' => $attendant,
                    'count' => $completed->count(),
                    'amount' => (float) $completed->sum('amount'),
                    'reversed' => $rows->where('status', CanteenSale::STATUS_REVERSED)->count(),
                    'last_sale_at' => $rows->max('created_at'),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    protected function topStudents(Collection $completed, int $limit = 10): Collection
    {
        $totals = $completed->whereNotNull('student_id')
            ->groupBy('student_id')
            ->map(fn (Collection $rows, $studentId) => [
                'student_id' => (int) $studentId,
                'count' => $rows->count(),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->take($limit);

        $students = Student::query()
            ->with(['classRoom:id,name', 'pocketPayCard:id,student_id,card_number,balance'])
            ->whereIn('id', $totals->keys())
            ->get(['id', 'first_name', 'last_name', 'class_room_id'])
            ->keyBy('id');

        return $totals
            ->map(fn (array $row) => $row + ['student' => $students->get($row['student_id'])])
            ->values();
    }

    protected function parseDate(?string $date, string $tz): ?CarbonImmutable
    {
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
