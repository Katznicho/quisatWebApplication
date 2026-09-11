<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupCode extends Model
{
    protected $fillable = [
        'business_id',
        'student_id',
        'attendance_id',
        'code_date',
        'code',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'code_date' => 'date',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isValid(): bool
    {
        if ($this->used_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
