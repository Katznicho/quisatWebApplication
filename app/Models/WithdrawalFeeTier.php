<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalFeeTier extends Model
{
    protected $fillable = [
        'business_id',
        'min_amount',
        'max_amount',
        'charge_amount',
        'sort_order',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'charge_amount' => 'integer',
        'sort_order' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function rangeLabel(): string
    {
        if ($this->max_amount === null) {
            return 'Above '.number_format($this->min_amount - 1);
        }

        return number_format($this->min_amount).' - '.number_format($this->max_amount);
    }
}
