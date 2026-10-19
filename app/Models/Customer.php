<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'business_id',
        'customer_reference',
        'name',
        'mobile_number',
        'email',
        'address',
    ];

    /**
     * Get the business that owns the customer.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the bills for the customer.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'customer_reference', 'customer_reference');
    }
}
