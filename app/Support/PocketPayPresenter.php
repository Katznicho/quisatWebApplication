<?php

namespace App\Support;

use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenFloatTransaction;
use App\Models\CanteenSale;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardTransaction;
use App\Models\PocketPayTopup;
use App\Models\Student;
use App\Services\PocketPayService;
use Illuminate\Support\Str;

/**
 * JSON shapes shared by the Quisat Attendant app and the parent app.
 */
class PocketPayPresenter
{
    public static function student(?Student $student): ?array
    {
        if (! $student) {
            return null;
        }

        $photo = $student->photo;

        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'first_name' => $student->first_name,
            'class' => $student->classRoom?->name,
            'photo_url' => empty($photo) ? null : (Str::startsWith($photo, ['http://', 'https://']) ? $photo : asset('storage/'.ltrim($photo, '/'))),
        ];
    }

    public static function card(PocketPayCard $card, bool $withSpending = true): array
    {
        $data = [
            'uuid' => $card->uuid,
            'card_number' => $card->card_number,
            'masked_number' => $card->maskedNumber(),
            'status' => $card->status,
            'is_locked' => $card->isLocked(),
            'locked_until' => $card->locked_until?->toISOString(),
            'has_pin' => filled($card->pin),
            'balance' => (float) $card->balance,
            'daily_limit' => $card->daily_limit !== null ? (float) $card->daily_limit : null,
            'activated_at' => $card->activated_at?->toISOString(),
            'student' => self::student($card->student),
            'school' => $card->business ? ['id' => $card->business->id, 'name' => $card->business->name] : null,
        ];

        if ($withSpending) {
            $service = app(PocketPayService::class);
            $data['spent_today'] = $service->spentToday($card);
            $data['remaining_today'] = $service->remainingToday($card);
        }

        return $data;
    }

    public static function canteen(Canteen $canteen): array
    {
        return [
            'uuid' => $canteen->uuid,
            'name' => $canteen->name,
            'location' => $canteen->location,
            'status' => $canteen->status,
            'float_balance' => (float) $canteen->float_balance,
            'settlement_mode' => $canteen->collectsSales() ? 'collect' : 'float',
            'is_float_low' => $canteen->isFloatLow(),
        ];
    }

    public static function attendant(CanteenAttendant $attendant): array
    {
        return [
            'uuid' => $attendant->uuid,
            'name' => $attendant->name,
            'phone' => $attendant->phone,
            'status' => $attendant->status,
            'canteen' => $attendant->canteen ? self::canteen($attendant->canteen) : null,
            'school' => $attendant->business ? [
                'id' => $attendant->business->id,
                'name' => $attendant->business->name,
                'logo_url' => $attendant->business->logo ? asset('storage/'.ltrim($attendant->business->logo, '/')) : null,
            ] : null,
        ];
    }

    public static function sale(CanteenSale $sale): array
    {
        return [
            'uuid' => $sale->uuid,
            'receipt_number' => $sale->receipt_number,
            'amount' => (float) $sale->amount,
            'description' => $sale->description,
            'status' => $sale->status,
            'reversal_status' => $sale->reversal_status,
            'reversal_reason' => $sale->reversal_reason,
            'reversal_review_note' => $sale->reversal_review_note,
            'can_request_reversal' => $sale->canRequestReversal(),
            'created_at' => $sale->created_at?->toISOString(),
            'card_number' => $sale->card?->maskedNumber(),
            'student' => self::student($sale->student),
            'canteen' => $sale->canteen?->name,
            'attendant' => $sale->attendant?->name,
        ];
    }

    public static function cardTransaction(PocketPayCardTransaction $transaction): array
    {
        return [
            'uuid' => $transaction->uuid,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'balance_after' => (float) $transaction->balance_after,
            'description' => $transaction->description,
            'canteen' => $transaction->canteen?->name,
            'reference' => $transaction->reference instanceof CanteenSale ? $transaction->reference->receipt_number : null,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }

    public static function floatTransaction(CanteenFloatTransaction $transaction): array
    {
        return [
            'uuid' => $transaction->uuid,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'balance_after' => (float) $transaction->balance_after,
            'description' => $transaction->description,
            'created_at' => $transaction->created_at?->toISOString(),
        ];
    }

    public static function topup(PocketPayTopup $topup, ?array $payment = null): array
    {
        return [
            'uuid' => $topup->uuid,
            'amount' => (float) $topup->amount,
            'phone_number' => $topup->phone_number,
            'status' => $topup->status,
            'credited_at' => $topup->credited_at?->toISOString(),
            'created_at' => $topup->created_at?->toISOString(),
            'payment' => $payment ? [
                'reference' => $payment['reference'] ?? null,
                'amount' => $payment['amount'] ?? null,
                'platform_charge' => $payment['platform_charge'] ?? null,
            ] : null,
        ];
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     */
    public static function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
