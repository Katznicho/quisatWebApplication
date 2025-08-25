<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'business_id',
        'status',
    ];

    protected $casts = [
        'uuid' => 'string',
        'business_id' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($branch) {
            $branch->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
