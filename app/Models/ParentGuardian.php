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

    public function clinicPatients()
    {
        return $this->hasMany(ClinicPatient::class);
    }

    public function children()
    {
        return $this->hasMany(ParentChild::class);
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

    public function resolveScopedBusiness(?int $requestedBusinessId = null): ?Business
    {
        if ($requestedBusinessId && $this->belongsToBusiness($requestedBusinessId)) {
            if ($this->relationLoaded('business') && (int) $this->business_id === $requestedBusinessId) {
                return $this->business;
            }

            return Business::query()->find($requestedBusinessId);
        }

        if ($this->business) {
            return $this->business;
        }

        $membership = $this->relationLoaded('memberships')
            ? $this->memberships->firstWhere('status', 'active')
            : $this->memberships()->where('status', 'active')->with('business')->first();

        return $membership?->business;
    }

    public function linkedChurchBusinessIds(): array
    {
        $ids = $this->activeBusinesses()
            ->get()
            ->filter(fn (Business $business) => $business->isChurch())
            ->pluck('id');

        if ($this->business?->isChurch()) {
            $ids->push($this->business->id);
        }

        return $ids->filter()->unique()->values()->all();
    }

    public function scopedChurchBusinessIds(?int $requestedBusinessId = null): array
    {
        $churchIds = collect($this->linkedChurchBusinessIds())
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($churchIds->isEmpty()) {
            return array_values(array_filter([(int) $requestedBusinessId]));
        }

        if ($requestedBusinessId && $churchIds->contains((int) $requestedBusinessId)) {
            return [(int) $requestedBusinessId];
        }

        return $churchIds->all();
    }

    public function preferredChurchBusinessId(?int $requestedBusinessId = null): ?int
    {
        $ids = $this->linkedChurchBusinessIds();
        if ($ids === []) {
            return null;
        }

        $scoped = $this->scopedChurchBusinessIds($requestedBusinessId);

        return (int) ($scoped[0] ?? $ids[0]);
    }

    public function studentsForChurchCheckIn(int $churchBusinessId)
    {
        app(ParentUniversalCodeService::class)->importChildrenToBusiness($this, $churchBusinessId);
        $this->unsetRelation('students');

        $students = $this->students()
            ->with(['classRoom:id,name,code'])
            ->where('business_id', $churchBusinessId)
            ->get();

        if ($students->isNotEmpty()) {
            return $students;
        }

        return $this->students()->with(['classRoom:id,name,code'])->get();
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where(function ($scope) use ($businessId) {
            $scope->where('business_id', $businessId)
                ->orWhereHas('memberships', function ($membership) use ($businessId) {
                    $membership->where('business_id', $businessId)
                        ->where('status', 'active');
                });
        });
    }

    public static function optionsForBusiness(int $businessId): array
    {
        return static::query()
            ->forBusiness($businessId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (self $parent) => [$parent->id => $parent->full_name])
            ->all();
    }
}
