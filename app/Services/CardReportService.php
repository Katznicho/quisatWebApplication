<?php

namespace App\Services;

use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardTransaction;
use App\Models\PocketPayTopup;
use App\Models\SmsMessage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Figures for the card detail page and its PDF statement. Periods are whole days in the
 * school's timezone, resolved the same way as the canteen page.
 */
class CardReportService
{
    public function __construct(
        protected CanteenReportService $canteens,
        protected PocketPayService $pocketPay,
    ) {}

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(PocketPayCard $card, ?string $from, ?string $to): array
    {
        return $this->canteens->periodIn($this->timezone($card), $from, $to);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->canteens->bounds($from, $to);
    }

    public function build(PocketPayCard $card, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $card->loadMissing('business', 'student.classRoom', 'student.parentGuardian');
        $tz = $this->timezone($card);
        [$start, $end] = $this->bounds($from, $to);

        $sales = CanteenSale::query()
            ->where('pocket_pay_card_id', $card->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('canteen:id,name')
            ->get(['id', 'canteen_id', 'amount', 'status', 'created_at']);
        $completed = $sales->where('status', CanteenSale::STATUS_COMPLETED);
        $spent = (float) $completed->sum('amount');

        $ledger = $this->ledgerQuery($card, $start, $end)->get();
        $opening = $this->balanceBefore($card, $start);
        $closing = $ledger->isNotEmpty() ? (float) $ledger->last()->balance_after : $opening;

        $allTime = CanteenSale::query()->where('pocket_pay_card_id', $card->id)->where('status', CanteenSale::STATUS_COMPLETED);

        return [
            'card' => $card,
            'from' => $from,
            'to' => $to,
            'timezone' => $tz,
            'generated_at' => CarbonImmutable::now($tz),
            'summary' => [
                'balance' => (float) $card->balance,
                'spent' => $spent,
                'purchases' => $completed->count(),
                'average_purchase' => $completed->count() > 0 ? $spent / $completed->count() : 0.0,
                'reversed_count' => $sales->where('status', CanteenSale::STATUS_REVERSED)->count(),
                'refunds' => (float) $ledger->where('type', 'reversal')->sum('amount'),
                'topups' => (float) $ledger->where('type', 'topup')->sum('amount'),
                'topup_count' => $ledger->where('type', 'topup')->count(),
                'opening' => $opening,
                'closing' => $closing,
                'credits' => (float) $ledger->where('direction', 'credit')->sum('amount'),
                'debits' => (float) $ledger->where('direction', 'debit')->sum('amount'),
                'spent_today' => $this->pocketPay->spentToday($card),
                'remaining_today' => $this->pocketPay->remainingToday($card),
                'pending_topups' => (float) $card->topups()->whereNull('credited_at')->whereIn('status', ['pending', 'processing'])->sum('amount'),
                'pending_reversals' => CanteenSale::query()->where('pocket_pay_card_id', $card->id)->where('reversal_status', CanteenSale::REVERSAL_PENDING)->count(),
                'lifetime_spent' => (float) (clone $allTime)->sum('amount'),
                'lifetime_purchases' => (clone $allTime)->count(),
                'first_purchase_at' => (clone $allTime)->min('created_at'),
                'last_purchase_at' => (clone $allTime)->max('created_at'),
            ],
            'daily' => $this->daily($completed, $from, $to, $tz),
            'canteens' => $this->byCanteen($completed),
            'ledger' => $ledger,
        ];
    }

    public function ledgerQuery(PocketPayCard $card, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return PocketPayCardTransaction::query()
            ->where('pocket_pay_card_id', $card->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('canteen:id,name')
            ->orderBy('id');
    }

    public function salesQuery(PocketPayCard $card, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return CanteenSale::query()
            ->where('pocket_pay_card_id', $card->id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['canteen:id,name', 'attendant:id,name', 'reviewer:id,name']);
    }

    public function topupsQuery(PocketPayCard $card, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return PocketPayTopup::query()
            ->where('topupable_type', PocketPayCard::class)
            ->where('topupable_id', $card->id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['initiatedBy', 'paymentCollections']);
    }

    /**
     * SMS alerts sent to the parent about this card: card actions, its sales and its top-ups.
     */
    public function alertsQuery(PocketPayCard $card): Builder
    {
        return SmsMessage::query()
            ->where('purpose', 'like', 'pocket_pay.%')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('related_type', PocketPayCard::class)->where('related_id', $card->id))
                ->orWhere(fn ($q) => $q->where('related_type', CanteenSale::class)
                    ->whereIn('related_id', CanteenSale::query()->select('id')->where('pocket_pay_card_id', $card->id)))
                ->orWhere(fn ($q) => $q->where('related_type', PocketPayTopup::class)
                    ->whereIn('related_id', PocketPayTopup::query()->select('id')
                        ->where('topupable_type', PocketPayCard::class)
                        ->where('topupable_id', $card->id))));
    }

    public function balanceBefore(PocketPayCard $card, CarbonImmutable $start): float
    {
        return (float) (PocketPayCardTransaction::query()
            ->where('pocket_pay_card_id', $card->id)
            ->where('created_at', '<', $start)
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }

    public function timezone(PocketPayCard $card): string
    {
        return $card->business?->timezoneName() ?? config('app.timezone');
    }

    public function filename(PocketPayCard $card, CarbonImmutable $from, CarbonImmutable $to): string
    {
        $name = $card->student?->full_name ?: $card->card_number;

        return str($name)->slug().'-card-statement-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';
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

    protected function byCanteen(Collection $completed): Collection
    {
        return $completed->groupBy('canteen_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->canteen?->name ?? 'Unknown canteen',
                'count' => $rows->count(),
                'amount' => (float) $rows->sum('amount'),
                'last_at' => $rows->max('created_at'),
            ])
            ->sortByDesc('amount')
            ->values();
    }
}
