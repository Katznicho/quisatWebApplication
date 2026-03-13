<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportChild extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_id',
        'child_name',
        'age',
        'monthly_fee',
        'currency',
        'story',
        'organisation_name',
        'organisation_email',
        'organisation_phone',
        'organisation_website',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'age' => 'integer',
        'is_featured' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (SupportChild $child) {
            if (empty($child->uuid)) {
                $child->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function images()
    {
        return $this->hasMany(SupportChildImage::class);
    }
}
