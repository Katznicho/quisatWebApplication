<?php

namespace App\Models;

use App\Services\ParentUniversalCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class ParentGuardian extends Model
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'country',
        'relationship',
        'occupation',
        'emergency_contact',
        'business_id',
        'status',
        'photo',
        'account_type',
        'universal_code',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (ParentGuardian $parentGuardian) {
            if (empty($parentGuardian->uuid)) {
                $parentGuardian->uuid = (string) Str::uuid();
            }

            if (empty($parentGuardian->account_type)) {
                $parentGuardian->account_type = $parentGuardian->business_id ? 'linked' : 'guest';
            }

            if (empty($parentGuardian->universal_code)) {
                $parentGuardian->universal_code = app(ParentUniversalCodeService::class)->generateUniqueCode();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ParentGuardianBusiness::class, 'parent_guardian_id');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Business::class,
            'parent_guardian_business',
            'parent_guardian_id',
            'business_id'
        )->withPivot(['relationship', 'joined_via', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function activeBusinesses(): BelongsToMany
    {
        return $this->businesses()->wherePivot('status', 'active');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function hiddenAssignments()
    {
        return $this->belongsToMany(
            ClassAssignment::class,
            'class_assignment_parent_hidden',
            'parent_guardian_id',
            'assignment_id'
        )->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getUniversalLinkAttribute(): ?string
    {
        return app(ParentUniversalCodeService::class)->universalLink($this->universal_code);
    }

    public function belongsToBusiness(int $businessId): bool
    {
        if ((int) $this->business_id === $businessId) {
            return true;
        }

        return $this->memberships()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->exists();
    }
}
