<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClinicAppointment extends Model
{
    use HasFactory;
    use InteractsWithMarzPay;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_patient_id',
        'clinic_service_id',
        'scheduled_at',
        'doctor_name',
        'appointment_type',
        'status',
        'notes',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'paid_at',
        'payment_notes',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (ClinicAppointment $appointment) {
            if (empty($appointment->uuid)) {
                $appointment->uuid = (string) Str::uuid();
            }

            if (empty($appointment->currency)) {
                $appointment->currency = 'UGX';
            }

            if (empty($appointment->payment_status)) {
                $appointment->payment_status = 'not_required';
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function patient()
    {
        return $this->belongsTo(ClinicPatient::class, 'clinic_patient_id');
    }

    public function clinicService()
    {
        return $this->belongsTo(ClinicService::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function marzPayAmount(): int
    {
        return max(0, (int) round((float) ($this->amount ?? 0)));
    }

    public function marzPayDescription(): string
    {
        $this->loadMissing(['clinicService', 'patient']);

        $service = $this->clinicService?->name
            ?? $this->appointment_type
            ?? 'Appointment';
        $patient = $this->patient?->full_name ?? 'Patient';

        return 'Clinic appointment: '.$service.' — '.$patient;
    }

    public function marzPayPhoneNumber(): ?string
    {
        $this->loadMissing('patient.parentGuardian');

        return $this->patient?->parentGuardian?->phone;
    }

    public function markMarzPayCompleted(PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markMarzPayFailed(PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }
}
