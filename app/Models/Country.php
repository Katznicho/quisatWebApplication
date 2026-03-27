<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency_code',
        'currency_name',
        'exchange_rate',
        'is_default',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_default' => 'boolean',
    ];

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }
}
