<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class SmsMessage extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'recipient',
        'message',
        'purpose',
        'related_type',
        'related_id',
        'status',
        'cost',
        'provider_transaction_id',
        'error',
        'response_payload',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SmsMessage $message) {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
