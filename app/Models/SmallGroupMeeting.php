<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SmallGroupMeeting extends Model
{
    protected $fillable = [
        'uuid',
        'small_group_id',
        'business_id',
        'meeting_date',
        'location',
        'created_by_user_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $meeting) {
            if (! $meeting->uuid) {
                $meeting->uuid = (string) Str::uuid();
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SmallGroup::class, 'small_group_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SmallGroupAttendance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
