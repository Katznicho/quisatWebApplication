<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicPatientGrowthRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_patient_id',
        'recorded_by',
        'recorded_on',
        'height_cm',
        'weight_kg',
        'head_circumference_cm',
        'notes',
    ];

    protected $casts = [
        'recorded_on' => 'date',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'head_circumference_cm' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicPatientGrowthRecord $record) {
            if (empty($record->uuid)) {
                $record->uuid = (string) Str::uuid();
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

    public function getBmiAttribute(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg) {
            return null;
        }

        $heightInMeters = ((float) $this->height_cm) / 100;
        if ($heightInMeters <= 0) {
            return null;
        }

        return round(((float) $this->weight_kg) / ($heightInMeters * $heightInMeters), 2);
    }
}
