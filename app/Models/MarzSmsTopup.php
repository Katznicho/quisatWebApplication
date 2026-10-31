<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarzSmsTopup extends Model
{
    protected $table = 'marzsms_topups';

    protected $fillable = [
        'reference',
        'amount',
        'charge',
        'total_amount',
        'phone_number',
        'status',
        'message',
        'response_payload',
        'initiated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'response_payload' => 'array',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }
}
