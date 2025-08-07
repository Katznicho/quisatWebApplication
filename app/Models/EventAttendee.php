<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAttendee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'program_event_id',
        'user_id',
        'child_name',
        'parent_name',
        'parent_phone',
        'parent_email',
        'child_age',
        'gender',
        'payment_method',
        'payment_reference',
        'amount_paid',
        'amount_due',
        'status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    // An attendee belongs to a program event
    public function programEvent()
    {
        return $this->belongsTo(ProgramEvent::class);
    }

    // An attendee belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // An attendee has many payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Get total amount paid from payments
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Get balance amount
    public function getBalanceAttribute()
    {
        return $this->amount_due - $this->getTotalPaidAttribute();
    }

    // Check if payment is complete
    public function getIsPaymentCompleteAttribute()
    {
        return $this->getTotalPaidAttribute() >= $this->amount_due;
    }

    // Get payment status
    public function getPaymentStatusAttribute()
    {
        $totalPaid = $this->getTotalPaidAttribute();
        if ($totalPaid >= $this->amount_due) {
            return 'paid';
        } elseif ($totalPaid > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
