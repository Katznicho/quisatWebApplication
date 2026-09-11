<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KidsVolunteer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'user_id',
        'name',
        'phone',
        'email',
        'role',
        'background_check_status',
        'hours_served',
        'notes',
        'status',
    ];

    protected $casts = [
        'hours_served' => 'decimal:1',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $volunteer) {
            if (empty($volunteer->uuid)) {
                $volunteer->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
