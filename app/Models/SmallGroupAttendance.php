<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmallGroupAttendance extends Model
{
    protected $fillable = [
        'small_group_meeting_id',
        'small_group_id',
        'business_id',
        'student_id',
        'status',
        'verification_status',
        'submitted_by_parent_id',
        'verified_by_user_id',
        'verified_at',
        'last_changed_by_user_id',
        'last_changed_by_parent_id',
        'last_changed_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_changed_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SmallGroupMeeting::class, 'small_group_meeting_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SmallGroup::class, 'small_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'submitted_by_parent_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function lastChangedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by_user_id');
    }

    public function lastChangedByParent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'last_changed_by_parent_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(SmallGroupAttendanceChange::class);
    }
}
