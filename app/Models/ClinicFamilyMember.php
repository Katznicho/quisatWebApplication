<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClinicFamilyMember extends Model
{
    use HasFactory;

    public const DEFAULT_PERMISSIONS = [
        'view_records' => true,
        'book_appointments' => true,
        'pickup_authorization' => false,
    ];

    protected $fillable = [
        'uuid',
        'clinic_family_id',
        'parent_guardian_id',
        'relationship',
        'permissions',
        'is_primary',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_primary' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (ClinicFamilyMember $member) {
            if (empty($member->uuid)) {
                $member->uuid = (string) Str::uuid();
            }

            if (empty($member->permissions)) {
                $member->permissions = self::DEFAULT_PERMISSIONS;
            }
        });
    }

    public function family()
    {
        return $this->belongsTo(ClinicFamily::class, 'clinic_family_id');
    }

    public function parentGuardian()
    {
        return $this->belongsTo(ParentGuardian::class);
    }
}
