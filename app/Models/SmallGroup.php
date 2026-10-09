<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SmallGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'name',
        'location',
        'host_name',
        'host_phone',
        'leader_name',
        'leader_phone',
        'leader_user_id',
        'capacity',
        'custom_values',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'custom_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $group) {
            if (! $group->uuid) {
                $group->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function leaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SmallGroupMember::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(SmallGroupMeeting::class);
    }

    public function availableSpaces(): int
    {
        $used = $this->members_count ?? $this->members()->count();

        return max(0, (int) $this->capacity - (int) $used);
    }
}
