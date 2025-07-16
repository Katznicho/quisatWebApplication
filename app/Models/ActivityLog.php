<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'action_type',
        'description',
        'date'
    ];

    //cast
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    //
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //
    public function business()
    {
        return $this->belongsTo(Business::class);
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
