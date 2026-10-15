<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChurchVisitor extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'family_id',
        'registered_by_parent_id',
        'full_name',
        'date_of_birth',
        'age',
        'relationship',
        'care_notes',
        'removed_at',
        'removed_by_parent_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'removed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChurchVisitor $visitor) {
            $visitor->uuid ??= (string) Str::uuid();
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'family_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'registered_by_parent_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ChurchVisitorRegistration::class);
    }

    public function ageLabel(): string
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age.' years';
        }

        return $this->age !== null ? $this->age.' years' : '';
    }
}
