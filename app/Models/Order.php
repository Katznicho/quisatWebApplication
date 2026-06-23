<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
use App\Support\StationeryHub;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
    use InteractsWithMarzPay;

    protected $fillable = [
        'uuid',
        'order_number',
        'business_id',
        'hub',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'notes',
        'status',
        'fulfillment_status',
        'payment_method',
        'payment_status',
        'wallet_credit_amount',
        'funds_released_at',
        'funds_released_by',
        'customer_received_at',
        'customer_received_by',
        'subtotal',
        'total',
        'total_amount',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'wallet_credit_amount' => 'decimal:2',
        'funds_released_at' => 'datetime',
        'customer_received_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $prefix = ($order->hub ?? StationeryHub::KIDZ_MART) === StationeryHub::HUB ? 'SH-' : 'KM-';
                $order->order_number = $prefix.strtoupper(Str::random(8));
            }
            if (empty($order->fulfillment_status)) {
                $order->fulfillment_status = 'new';
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function marzPayAmount(): int
    {
        return (int) round((float) ($this->total_amount ?? $this->total ?? 0));
    }

    public function marzPayDescription(): string
    {
        return ($this->hub ?? StationeryHub::KIDZ_MART) === StationeryHub::HUB
            ? 'Stationery Hub order '.$this->order_number
            : 'Kidz Mart order '.$this->order_number;
    }

    public function marzPayPhoneNumber(): ?string
    {
        return $this->customer_phone;
    }

    public function markMarzPayCompleted(\App\Models\PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => in_array($this->status, ['pending', 'confirmed'], true) ? 'processing' : $this->status,
        ]);
    }

    public function fundsAreHeld(): bool
    {
        if ($this->funds_released_at || $this->payment_status !== 'paid') {
            return false;
        }

        return in_array($this->payment_method, ['mtn_mobile_money', 'airtel_money', 'card'], true);
    }

    public function fundsReleasedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'funds_released_by');
    }

    public function customerReceivedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_received_by');
    }

    public function customerHasConfirmedReceipt(): bool
    {
        return $this->customer_received_at !== null;
    }

    public function customerCanConfirmReceipt(): bool
    {
        if ($this->customer_received_at || $this->status === 'cancelled') {
            return false;
        }

        if ($this->payment_status === 'paid') {
            return true;
        }

        return in_array($this->status, ['confirmed', 'processing', 'shipped', 'delivered'], true);
    }

    public function markMarzPayFailed(\App\Models\PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }
}
