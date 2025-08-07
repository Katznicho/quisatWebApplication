<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'event_attendee_id',
        'amount',
        'payment_method',
        'payment_reference',
        'notes',
        'payment_date',
        'user_id', // who recorded the payment
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // A payment belongs to an event attendee
    public function eventAttendee()
    {
        return $this->belongsTo(EventAttendee::class);
    }

    // A payment belongs to a user (who recorded it)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get formatted payment date
    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('M d, Y') : 'N/A';
    }

    // Get payment method display name
    public function getPaymentMethodDisplayAttribute()
    {
        $methods = [
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'airtel_money' => 'Airtel Money',
            'mtn_mobile_money' => 'MTN Mobile Money',
            'other' => 'Other',
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            $payment->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
