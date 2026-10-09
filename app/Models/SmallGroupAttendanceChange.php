<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupAttendanceChange extends Model
{
    protected $fillable = [
        'small_group_attendance_id',
        'actor_user_id',
        'actor_parent_id',
        'action',
        'summary',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(SmallGroupAttendance::class, 'small_group_attendance_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorParent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'actor_parent_id');
    }
}
