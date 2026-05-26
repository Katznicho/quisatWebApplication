<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicPatientVaccination extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_patient_id',
        'recorded_by',
        'vaccine_name',
        'dose_label',
        'scheduled_date',
        'administered_date',
        'next_due_date',
        'status',
        'batch_number',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'administered_date' => 'date',
        'next_due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicPatientVaccination $vaccination) {
            if (empty($vaccination->uuid)) {
                $vaccination->uuid = (string) Str::uuid();
            }
        });
    }

    public function patient()
    {
        return $this->belongsTo(ClinicPatient::class, 'clinic_patient_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
