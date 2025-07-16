<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transaction extends Model
{
    use HasFactory, SoftDeletes;



     protected $fillable = [
        'business_id',
        'amount',
        'reference',
        'description',
        'status',
        'type',
        'origin',
        'phone_number',
        'provider',
        'service',
        'date',
        'currency',
        'names',
        'email',
        'ip_address',
        'user_agent',
        'date',
        'method',
        'transaction_for'
     ];

     //relationship
     public function user()
     {
         return $this->belongsTo(User::class);
     }

     //relationship
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
