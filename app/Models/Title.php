<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model
{
    use HasFactory;
     use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'description',
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
        static::creating(function ($title) {
            $title->uuid = (string) Str::uuid();
        });
    }
}
