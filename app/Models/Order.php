<?php

namespace App\Models;

use App\Models\Concerns\InteractsWithMarzPay;
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
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'notes',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'total',
        'total_amount',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $order->order_number = 'KM-' . strtoupper(Str::random(8));
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
        return 'Kidz Mart order '.$this->order_number;
    }

    public function marzPayPhoneNumber(): ?string
    {
        return $this->customer_phone;
    }

    public function markMarzPayCompleted(\App\Models\PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => $this->status === 'pending' ? 'confirmed' : $this->status,
        ]);
    }

    public function markMarzPayFailed(\App\Models\PaymentCollection $collection): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }
}
