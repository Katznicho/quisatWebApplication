<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BusinessReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'order_id',
        'user_id',
        'hub',
        'rating',
        'title',
        'comment',
        'status',
        'verified_purchase',
        'reviewer_name',
    ];

    protected $casts = [
        'rating' => 'integer',
        'verified_purchase' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BusinessReview $review) {
            if (empty($review->uuid)) {
                $review->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
