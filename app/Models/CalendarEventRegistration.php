<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CalendarEventRegistration extends Model
{
    use HasFactory;
    use InteractsWithMarzPay;

    protected $fillable = [
        'uuid',
        'calendar_event_id',
        'user_id',
        'parent_guardian_id',
        'child_name',
        'child_age',
        'parent_name',
        'parent_email',
        'parent_phone',
        'emergency_contact',
        'medical_conditions',
        'dietary_restrictions',
        'payment_method',
        'payment_status',
        'registration_status',
        'notes',
    ];

    protected $casts = [
        'child_age' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (CalendarEventRegistration $registration) {
            if (empty($registration->uuid)) {
                $registration->uuid = (string) Str::uuid();
            }
        });
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public function marzPayAmount(): int
    {
        return (int) round((float) ($this->calendarEvent?->price ?? 0));
    }

    public function marzPayDescription(): string
    {
        return 'School event registration: '.($this->calendarEvent?->title ?? 'Event');
    }

    public function marzPayPhoneNumber(): ?string
    {
        return $this->parent_phone;
    }

    public function markMarzPayCompleted(PaymentCollection $collection): void
    {
        $this->update(['payment_status' => 'paid']);
    }

    public function markMarzPayFailed(PaymentCollection $collection): void
    {
        $this->update(['payment_status' => 'failed']);
    }
}
