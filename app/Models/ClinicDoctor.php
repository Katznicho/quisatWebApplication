<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicDoctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'specialization',
        'phone',
        'email',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicDoctor $doctor) {
            if (empty($doctor->uuid)) {
                $doctor->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
