<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StaffAttendance extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'branch_id',
        'user_id',
        'recorded_by_user_id',
        'checked_out_by_user_id',
        'attendance_date',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (! $record->uuid) {
                $record->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(StaffAttendanceCorrection::class);
    }
}
