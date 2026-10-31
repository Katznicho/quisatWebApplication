<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CanteenSale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVERSED = 'reversed';

    public const REVERSAL_PENDING = 'pending';

    public const REVERSAL_APPROVED = 'approved';

    public const REVERSAL_REJECTED = 'rejected';

    protected $fillable = [
        'uuid',
        'receipt_number',
        'business_id',
        'canteen_id',
        'canteen_attendant_id',
        'pocket_pay_card_id',
        'student_id',
        'amount',
        'description',
        'status',
        'reversal_status',
        'reversal_reason',
        'reversal_requested_at',
        'reversal_reviewed_by',
        'reversal_reviewed_at',
        'reversal_review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reversal_requested_at' => 'datetime',
        'reversal_reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CanteenSale $sale) {
            if (empty($sale->uuid)) {
                $sale->uuid = (string) Str::uuid();
            }

            if (empty($sale->receipt_number)) {
                $sale->receipt_number = static::generateReceiptNumber();
            }
        });
    }

    public static function generateReceiptNumber(): string
    {
        do {
            $number = 'QPP'.now()->format('ymd').strtoupper(Str::random(6));
        } while (static::query()->where('receipt_number', $number)->exists());

        return $number;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(CanteenAttendant::class, 'canteen_attendant_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(PocketPayCard::class, 'pocket_pay_card_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversal_reviewed_by');
    }

    public function canRequestReversal(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->reversal_status === null;
    }

    public function hasPendingReversal(): bool
    {
        return $this->reversal_status === self::REVERSAL_PENDING;
    }
}
