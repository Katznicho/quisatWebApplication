<?php

namespace App\Http\Controllers\API\PocketPay;

use App\Exceptions\PocketPayException;
use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\PocketPayCard;
use App\Models\PocketPayTopup;
use App\Services\MarzPayService;
use App\Services\PocketPayService;
use App\Support\PocketPayPresenter;
use Illuminate\Http\Request;

/**
 * Parent app: see, activate, re-PIN, limit, top up and get statements for their children's Pocket Pay cards.
 */
class ParentPocketPayController extends Controller
{
    public function __construct(protected PocketPayService $pocketPay) {}

    public function index(Request $request)
    {
        $parent = $this->parent($request);

        $cards = PocketPayCard::query()
            ->whereIn('student_id', $parent->students()->select('id'))
            ->with('student.classRoom', 'business')
            ->get()
            ->filter(fn (PocketPayCard $card) => $this->pocketPay->isEnabledFor($card->business))
            ->map(fn (PocketPayCard $card) => PocketPayPresenter::card($card))
            ->values();

        return response()->json(['success' => true, 'data' => ['cards' => $cards]]);
    }

    public function show(Request $request, PocketPayCard $card)
    {
        $this->authorizeCard($request, $card);

        return response()->json(['success' => true, 'data' => ['card' => PocketPayPresenter::card($card)]]);
    }

    public function activate(Request $request, PocketPayCard $card)
    {
        $validated = $request->validate(['pin' => 'required|string|size:4|confirmed']);
        $this->authorizeCard($request, $card);

        $this->pocketPay->activateCard($card, $validated['pin'], $this->parent($request));

        return response()->json([
            'success' => true,
            'message' => 'Card activated. Share the PIN with your child privately.',
            'data' => ['card' => PocketPayPresenter::card($card->fresh(['student.classRoom', 'business']))],
        ]);
    }

    public function resetPin(Request $request, PocketPayCard $card)
    {
        $validated = $request->validate(['pin' => 'required|string|size:4|confirmed']);
        $this->authorizeCard($request, $card);

        $this->pocketPay->resetCardPin($card, $validated['pin'], $this->parent($request));

        return response()->json(['success' => true, 'message' => 'Card PIN has been reset.']);
    }

    public function updateLimit(Request $request, PocketPayCard $card)
    {
        $validated = $request->validate(['daily_limit' => 'nullable|numeric|min:0|max:10000000']);
        $this->authorizeCard($request, $card);

        $limit = isset($validated['daily_limit']) ? (float) $validated['daily_limit'] : null;
        $this->pocketPay->setDailyLimit($card, $limit, $this->parent($request));

        return response()->json([
            'success' => true,
            'message' => $limit === null ? 'Daily limit removed.' : 'Daily limit updated.',
            'data' => ['card' => PocketPayPresenter::card($card->fresh(['student.classRoom', 'business']))],
        ]);
    }

    public function statement(Request $request, PocketPayCard $card)
    {
        $validated = $request->validate(['per_page' => 'nullable|integer|min:1|max:100']);
        $this->authorizeCard($request, $card);

        $transactions = $card->transactions()
            ->with('canteen', 'reference')
            ->latest('id')
            ->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'success' => true,
            'data' => [
                'card' => PocketPayPresenter::card($card),
                'transactions' => $transactions->getCollection()->map(fn ($t) => PocketPayPresenter::cardTransaction($t))->values(),
                'pagination' => PocketPayPresenter::pagination($transactions),
            ],
        ]);
    }

    public function topup(Request $request, PocketPayCard $card)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'phone_number' => 'nullable|string|max:20',
        ]);
        $this->authorizeCard($request, $card);

        $parent = $this->parent($request);
        $phone = $validated['phone_number'] ?? $parent->phone;

        if (! $phone) {
            throw new PocketPayException('Enter the mobile money number to charge.', 422, 'invalid_phone');
        }

        $result = $this->pocketPay->initiateTopup($card, (float) $validated['amount'], $phone, $parent);

        return response()->json([
            'success' => true,
            'message' => 'Approve the mobile money prompt on your phone to complete the top-up.',
            'data' => ['topup' => PocketPayPresenter::topup($result['topup'], $result['payment'])],
        ]);
    }

    public function topupStatus(Request $request, PocketPayTopup $topup, MarzPayService $marzPay)
    {
        $parent = $this->parent($request);

        if ($topup->initiated_by_type !== ParentGuardian::class || $topup->initiated_by_id !== $parent->id) {
            throw new PocketPayException('Top-up not found.', 404);
        }

        if ($topup->credited_at === null && in_array($topup->status, ['pending', 'processing'], true)) {
            $collection = $topup->latestPaymentCollection();

            if ($collection && ! $collection->isFinal()) {
                $marzPay->syncCollectionStatus($collection);
                $topup->refresh();
            }
        }

        return response()->json(['success' => true, 'data' => ['topup' => PocketPayPresenter::topup($topup)]]);
    }

    protected function parent(Request $request): ParentGuardian
    {
        $user = $request->user();

        if (! $user instanceof ParentGuardian) {
            throw new PocketPayException('Only parents/guardians can access Pocket Pay cards.', 403);
        }

        return $user;
    }

    protected function authorizeCard(Request $request, PocketPayCard $card): void
    {
        $card->loadMissing('student.classRoom', 'student.parentGuardian', 'business');

        if ($card->student?->parent_guardian_id !== $this->parent($request)->id
            || ! $this->pocketPay->isEnabledFor($card->business)) {
            throw new PocketPayException('Card not found.', 404, 'card_not_found');
        }
    }
}
