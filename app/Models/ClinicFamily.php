<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClinicFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'access_code',
        'family_name',
        'primary_parent_guardian_id',
        'notes',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function (ClinicFamily $family) {
            if (empty($family->uuid)) {
                $family->uuid = (string) Str::uuid();
            }

            // Clinic families linked from school use child codes (CHD-), not auto-generated KCL- codes.
        });
    }

    public static function generateUniqueAccessCode(int $businessId): string
    {
        do {
            $code = 'KCL-'.strtoupper(Str::random(6));
        } while (static::where('business_id', $businessId)->where('access_code', $code)->exists());

        return $code;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function primaryParent()
    {
        return $this->belongsTo(ParentGuardian::class, 'primary_parent_guardian_id');
    }

    public function patients()
    {
        return $this->hasMany(ClinicPatient::class);
    }

    public function members()
    {
        return $this->hasMany(ClinicFamilyMember::class);
    }
}
