<?php

namespace App\Http\Controllers\API\PocketPay;

use App\Exceptions\PocketPayException;
use App\Http\Controllers\Controller;
use App\Models\CanteenAttendant;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\PocketPayWithdrawal;
use App\Models\PocketPayTopup;
use App\Services\MarzPayService;
use App\Services\PocketPayService;
use App\Support\PocketPayPresenter;
use Illuminate\Http\Request;

/**
 * Quisat Attendant: card lookup/activation/PIN reset/top-up, sales, reversal requests and the float statement.
 */
class AttendantController extends Controller
{
    public function __construct(protected PocketPayService $pocketPay) {}

    public function dashboard(Request $request)
    {
        $attendant = $this->attendant($request);
        $canteen = $attendant->canteen;
        $todaySales = $canteen->sales()
            ->where('status', CanteenSale::STATUS_COMPLETED)
            ->whereDate('created_at', today());

        return response()->json([
            'success' => true,
            'data' => [
                'attendant' => PocketPayPresenter::attendant($attendant),
                'today' => [
                    'sales_count' => (clone $todaySales)->count(),
                    'sales_total' => (float) (clone $todaySales)->sum('amount'),
                    'my_sales_total' => (float) (clone $todaySales)->where('canteen_attendant_id', $attendant->id)->sum('amount'),
                ],
                'pending_reversals' => $canteen->sales()->where('reversal_status', CanteenSale::REVERSAL_PENDING)->count(),
            ],
        ]);
    }

    public function showCard(Request $request, string $cardNumber)
    {
        $card = $this->card($request, $cardNumber);

        return response()->json(['success' => true, 'data' => ['card' => PocketPayPresenter::card($card)]]);
    }

    public function activateCard(Request $request, string $cardNumber)
    {
        $validated = $request->validate(['pin' => 'required|string|size:4|confirmed']);
        $card = $this->card($request, $cardNumber);

        $this->pocketPay->activateCard($card, $validated['pin'], $this->attendant($request));

        return response()->json([
            'success' => true,
            'message' => 'Card activated. The student can now buy with their PIN.',
            'data' => ['card' => PocketPayPresenter::card($card->fresh(['student.classRoom', 'business']))],
        ]);
    }

    public function resetCardPin(Request $request, string $cardNumber)
    {
        $validated = $request->validate(['pin' => 'required|string|size:4|confirmed']);
        $card = $this->card($request, $cardNumber);

        $this->pocketPay->resetCardPin($card, $validated['pin'], $this->attendant($request));

        return response()->json(['success' => true, 'message' => 'Card PIN has been reset.']);
    }

    public function topupCard(Request $request, string $cardNumber)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'phone_number' => 'required|string|max:20',
        ]);
        $card = $this->card($request, $cardNumber);

        $result = $this->pocketPay->initiateTopup($card, (float) $validated['amount'], $validated['phone_number'], $this->attendant($request));

        return response()->json([
            'success' => true,
            'message' => 'Mobile money prompt sent. Ask the payer to approve it on their phone.',
            'data' => ['topup' => PocketPayPresenter::topup($result['topup'], $result['payment'])],
        ]);
    }

    public function topupStatus(Request $request, PocketPayTopup $topup, MarzPayService $marzPay)
    {
        $attendant = $this->attendant($request);

        if ($topup->business_id !== $attendant->business_id
            || $topup->initiated_by_type !== CanteenAttendant::class
            || $topup->initiated_by_id !== $attendant->id) {
            throw new PocketPayException('Top-up not found.', 404);
        }

        return response()->json(['success' => true, 'data' => ['topup' => $this->refreshTopup($topup, $marzPay)]]);
    }

    public function makeSale(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string|max:32',
            'amount' => 'required|numeric|min:1|max:10000000',
            'pin' => 'required|string|size:4',
            'description' => 'nullable|string|max:255',
        ]);

        $sale = $this->pocketPay->makeSale(
            $this->attendant($request),
            $validated['card_number'],
            (float) $validated['amount'],
            $validated['pin'],
            $validated['description'] ?? null,
        );

        $sale->load('card', 'student.classRoom', 'canteen', 'attendant');

        return response()->json([
            'success' => true,
            'message' => 'Sale completed.',
            'data' => [
                'sale' => PocketPayPresenter::sale($sale),
                'card_balance' => (float) $sale->card->balance,
                'float_balance' => (float) $sale->canteen->float_balance,
            ],
        ], 201);
    }

    public function sales(Request $request)
    {
        $attendant = $this->attendant($request);
        $validated = $request->validate([
            'date' => 'nullable|date',
            'mine' => 'nullable|boolean',
            'reversal_status' => 'nullable|in:pending,approved,rejected',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $sales = CanteenSale::query()
            ->where('canteen_id', $attendant->canteen_id)
            ->when($validated['date'] ?? null, fn ($q, $date) => $q->whereDate('created_at', $date))
            ->when($request->boolean('mine'), fn ($q) => $q->where('canteen_attendant_id', $attendant->id))
            ->when($validated['reversal_status'] ?? null, fn ($q, $status) => $q->where('reversal_status', $status))
            ->with('card', 'student.classRoom', 'canteen', 'attendant')
            ->latest()
            ->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $sales->getCollection()->map(fn ($sale) => PocketPayPresenter::sale($sale))->values(),
                'pagination' => PocketPayPresenter::pagination($sales),
            ],
        ]);
    }

    public function showSale(Request $request, CanteenSale $sale)
    {
        $this->assertOwnCanteen($request, $sale);
        $sale->load('card', 'student.classRoom', 'canteen', 'attendant');

        return response()->json(['success' => true, 'data' => ['sale' => PocketPayPresenter::sale($sale)]]);
    }

    public function requestReversal(Request $request, CanteenSale $sale)
    {
        $validated = $request->validate(['reason' => 'required|string|min:5|max:255']);
        $this->assertOwnCanteen($request, $sale);

        $sale = $this->pocketPay->requestReversal($this->attendant($request), $sale, $validated['reason']);
        $sale->load('card', 'student.classRoom', 'canteen', 'attendant');

        return response()->json([
            'success' => true,
            'message' => 'Reversal requested. Quisat must approve it before the money is returned.',
            'data' => ['sale' => PocketPayPresenter::sale($sale)],
        ]);
    }

    public function floatStatement(Request $request)
    {
        $canteen = $this->attendant($request)->canteen;
        $validated = $request->validate(['per_page' => 'nullable|integer|min:1|max:100']);

        $transactions = $canteen->floatTransactions()->latest('id')->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'success' => true,
            'data' => [
                'canteen' => PocketPayPresenter::canteen($canteen),
                'transactions' => $transactions->getCollection()->map(fn ($t) => PocketPayPresenter::floatTransaction($t))->values(),
                'pagination' => PocketPayPresenter::pagination($transactions),
            ],
        ]);
    }

    public function withdrawals(Request $request)
    {
        $attendant = $this->attendant($request);
        $canteen = $attendant->canteen;

        $rows = PocketPayWithdrawal::query()
            ->where('canteen_id', $canteen->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (PocketPayWithdrawal $withdrawal): array => [
                'uuid' => $withdrawal->uuid,
                'amount' => (float) $withdrawal->amount,
                'phone_number' => $withdrawal->phone_number,
                'status' => $withdrawal->status,
                'notes' => $withdrawal->notes,
                'created_at' => $withdrawal->created_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'canteen' => PocketPayPresenter::canteen($canteen),
                'can_withdraw' => $canteen->attendantCanWithdraw($attendant),
                'withdrawals' => $rows,
            ],
        ]);
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:5000000',
            'phone_number' => 'required|string|max:20',
            'notes' => 'nullable|string|max:255',
        ]);

        $attendant = $this->attendant($request);
        $withdrawal = $this->pocketPay->withdrawCanteenBalance(
            $attendant,
            $attendant->canteen,
            (float) $validated['amount'],
            $validated['phone_number'],
            $validated['notes'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal sent.',
            'data' => [
                'canteen' => PocketPayPresenter::canteen($attendant->canteen->fresh()),
                'withdrawal' => [
                    'uuid' => $withdrawal->uuid,
                    'amount' => (float) $withdrawal->amount,
                    'phone_number' => $withdrawal->phone_number,
                    'status' => $withdrawal->status,
                ],
            ],
        ], 201);
    }

    protected function attendant(Request $request): CanteenAttendant
    {
        return $request->user();
    }

    protected function card(Request $request, string $cardNumber): PocketPayCard
    {
        $card = $this->pocketPay->findCard($this->attendant($request)->business, $cardNumber);

        if (! $card) {
            throw new PocketPayException('Card not found at this school. Check the number on the card.', 404, 'card_not_found');
        }

        return $card->loadMissing('business');
    }

    protected function assertOwnCanteen(Request $request, CanteenSale $sale): void
    {
        if ($sale->canteen_id !== $this->attendant($request)->canteen_id) {
            throw new PocketPayException('Sale not found.', 404, 'sale_not_found');
        }
    }

    /**
     * Pull the latest MarzPay status for a pending top-up in case the webhook is late.
     */
    protected function refreshTopup(PocketPayTopup $topup, MarzPayService $marzPay): array
    {
        if ($topup->credited_at === null && in_array($topup->status, ['pending', 'processing'], true)) {
            $collection = $topup->latestPaymentCollection();

            if ($collection && ! $collection->isFinal()) {
                $marzPay->syncCollectionStatus($collection);
                $topup->refresh();
            }
        }

        return PocketPayPresenter::topup($topup);
    }
}
