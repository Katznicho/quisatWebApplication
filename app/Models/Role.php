<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'description',
        'branch_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'business_id' => 'integer',
        'branch_id' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted()
    {
        static::creating(function ($role) {
            $role->uuid = (string) Str::uuid();
        });
    }
}
