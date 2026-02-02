<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ParentCornerRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'parent_corner_id',
        'user_id',
        'parent_name',
        'parent_email',
        'parent_phone',
        'parent_address',
        'number_of_children',
        'interests',
        'notes',
        'payment_method',
        'payment_status',
        'registration_status',
    ];

    protected $casts = [
        'number_of_children' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (ParentCornerRegistration $registration) {
            if (empty($registration->uuid)) {
                $registration->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function parentCorner(): BelongsTo
    {
        return $this->belongsTo(ParentCorner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
