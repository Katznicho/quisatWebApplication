<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ParentChild extends Model
{
    protected $table = 'parent_children';

    protected $fillable = [
        'uuid',
        'parent_guardian_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'city',
        'country',
        'medical_notes',
        'allergies',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (ParentChild $child) {
            if (empty($child->uuid)) {
                $child->uuid = (string) Str::uuid();
            }
        });
    }

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
