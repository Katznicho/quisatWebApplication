<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Card numbers issued by Quisat before they are linked to a student. A number is
 * available until "Assign card" uses it, and retired when a card is renumbered.
 */
class PocketPayCardNumber extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_RETIRED = 'retired';

    public const MAX_BATCH = 500;

    protected $fillable = [
        'number',
        'batch',
        'status',
        'pocket_pay_card_id',
        'assigned_at',
        'created_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (PocketPayCardNumber $cardNumber) {
            $cardNumber->number = PocketPayCard::normalizeNumber($cardNumber->number);
        });
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(PocketPayCard::class, 'pocket_pay_card_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * True when the number is already in stock or on a card.
     */
    public static function isTaken(string $number, ?int $ignoreCardId = null): bool
    {
        $number = PocketPayCard::normalizeNumber($number);

        return static::query()->where('number', $number)->exists()
            || PocketPayCard::query()
                ->where('card_number', $number)
                ->when($ignoreCardId, fn ($q) => $q->whereKeyNot($ignoreCardId))
                ->exists();
    }

    /**
     * Create unique 12-digit numbers: 2-digit year, 9 random digits and a Luhn check digit
     * (so a mistyped number is rejected instead of matching another card).
     *
     * @return Collection<int, PocketPayCardNumber>
     */
    public static function generate(int $count, ?string $batch = null, ?int $createdBy = null): Collection
    {
        $count = max(1, min($count, self::MAX_BATCH));
        $created = collect();

        while ($created->count() < $count) {
            $number = self::withCheckDigit(now()->format('y').str_pad((string) random_int(0, 999_999_999), 9, '0', STR_PAD_LEFT));

            if (static::isTaken($number)) {
                continue;
            }

            $created->push(static::create([
                'number' => $number,
                'batch' => $batch,
                'status' => self::STATUS_AVAILABLE,
                'created_by' => $createdBy,
            ]));
        }

        return $created;
    }

    public static function withCheckDigit(string $digits): string
    {
        $sum = 0;

        foreach (array_reverse(str_split($digits)) as $i => $digit) {
            $value = (int) $digit * ($i % 2 === 0 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return $digits.((10 - $sum % 10) % 10);
    }

    /**
     * "260412344821" → "2604 1234 4821".
     */
    public static function format(?string $number): string
    {
        return trim(chunk_split((string) $number, 4, ' '));
    }
}
