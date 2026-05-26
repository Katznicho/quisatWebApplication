<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicPatient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_family_id',
        'parent_guardian_id',
        'student_id',
        'school_access_code',
        'patient_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'allergies',
        'emergency_contacts',
        'insurance_info',
        'photo',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'allergies' => 'array',
        'emergency_contacts' => 'array',
        'insurance_info' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (ClinicPatient $patient) {
            if (empty($patient->uuid)) {
                $patient->uuid = (string) Str::uuid();
            }

            if (empty($patient->patient_number) && $patient->business_id) {
                $count = static::withTrashed()
                    ->where('business_id', $patient->business_id)
                    ->count();
                $patient->patient_number = 'PAT-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function family()
    {
        return $this->belongsTo(ClinicFamily::class, 'clinic_family_id');
    }

    public function parentGuardian()
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function appointments()
    {
        return $this->hasMany(ClinicAppointment::class);
    }
}
