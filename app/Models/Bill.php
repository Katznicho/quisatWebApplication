<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'business_id',
        'customer_reference',
        'organization_reference',
        'customer_name',
        'amount',
        'currency',
        'bill_type',
        'credit_account_identifier',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the business that owns the bill.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the customer for this bill.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_reference', 'customer_reference');
    }
}
