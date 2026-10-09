<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupSetting extends Model
{
    protected $fillable = [
        'business_id',
        'module_label',
        'singular_label',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function forBusiness(int $businessId): self
    {
        return static::firstOrCreate(
            ['business_id' => $businessId],
            [
                'module_label' => 'Small Groups',
                'singular_label' => 'Small group',
                'custom_fields' => [],
            ]
        );
    }

    public function labels(): array
    {
        return [
            'module' => $this->module_label ?: 'Small Groups',
            'singular' => $this->singular_label ?: 'Small group',
        ];
    }
}
