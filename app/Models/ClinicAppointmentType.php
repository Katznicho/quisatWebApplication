<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicAppointmentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'applies_to',
        'status',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicAppointmentType $type) {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
