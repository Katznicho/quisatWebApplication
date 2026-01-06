<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KidsEventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'kids_event_id',
        'user_id', // nullable for guest registrations
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
        static::creating(function (KidsEventRegistration $registration) {
            if (empty($registration->uuid)) {
                $registration->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function kidsEvent(): BelongsTo
    {
        return $this->belongsTo(KidsEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

