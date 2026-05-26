<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicPatientVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_patient_id',
        'created_by',
        'visited_at',
        'doctor_name',
        'visit_type',
        'status',
        'chief_complaint',
        'consultation_notes',
        'treatment_plan',
        'prescriptions',
        'lab_results',
        'follow_up_date',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'follow_up_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicPatientVisit $visit) {
            if (empty($visit->uuid)) {
                $visit->uuid = (string) Str::uuid();
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(ClinicPatient::class, 'clinic_patient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
