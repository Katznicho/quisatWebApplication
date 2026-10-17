<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WalkinChild extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'walkin_family_id',
        'full_name',
        'date_of_birth',
        'age',
        'care_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $child) {
            $child->uuid ??= (string) Str::uuid();
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(WalkinFamily::class, 'walkin_family_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(WalkinVisit::class);
    }

    public function ageLabel(): string
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age.' years';
        }

        return $this->age !== null ? $this->age.' years' : '';
    }
}
