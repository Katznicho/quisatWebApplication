<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ParentCornerRegistration extends Model
{
    use HasFactory, SoftDeletes;
    use InteractsWithMarzPay;

    protected $fillable = [
        'uuid',
        'parent_corner_id',
        'user_id',
        'parent_name',
        'parent_email',
        'parent_phone',
        'parent_address',
        'number_of_children',
        'interests',
        'notes',
        'payment_method',
        'payment_status',
        'registration_status',
    ];

    protected $casts = [
        'number_of_children' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (ParentCornerRegistration $registration) {
            if (empty($registration->uuid)) {
                $registration->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function parentCorner(): BelongsTo
    {
        return $this->belongsTo(ParentCorner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marzPayAmount(): int
    {
        return (int) round((float) ($this->parentCorner?->price ?? 0));
    }

    public function marzPayDescription(): string
    {
        return 'Parent corner registration: '.($this->parentCorner?->title ?? 'Event');
    }

    public function marzPayPhoneNumber(): ?string
    {
        return $this->parent_phone;
    }

    public function markMarzPayCompleted(\App\Models\PaymentCollection $collection): void
    {
        $this->update(['payment_status' => 'paid']);
    }

    public function markMarzPayFailed(\App\Models\PaymentCollection $collection): void
    {
        $this->update(['payment_status' => 'failed']);
    }
}
