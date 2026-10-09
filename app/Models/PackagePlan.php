<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePlan extends Model
{
    protected $fillable = [
        'key',
        'name',
        'price',
        'currency_code',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function priceLabel(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        if ((float) $this->price <= 0) {
            return 'No charge';
        }

        return trim(($this->currency_code ?: '').' '.number_format((float) $this->price, 0));
    }
}
