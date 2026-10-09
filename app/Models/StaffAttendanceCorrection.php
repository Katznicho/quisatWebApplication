<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceCorrection extends Model
{
    protected $fillable = [
        'staff_attendance_id',
        'corrected_by_user_id',
        'reason',
        'original_checked_in_at',
        'original_checked_out_at',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'original_checked_in_at' => 'datetime',
        'original_checked_out_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StaffAttendance::class, 'staff_attendance_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
