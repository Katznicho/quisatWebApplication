<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'business_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'business_id' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    protected static function booted()
    {
        static::creating(function ($servicePoint) {
            $servicePoint->uuid = (string) Str::uuid();
        });
    }
}
