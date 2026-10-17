<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalkinVisit extends Model
{
    protected $fillable = [
        'walkin_checkin_id',
        'walkin_child_id',
        'walkin_family_id',
        'business_id',
        'church_service_id',
        'service_date',
        'service_name',
        'class_room_id',
        'status',
        'pickup_code',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'collector_name',
        'exception_reason',
        'active_key',
    ];

    protected $casts = [
        'service_date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(WalkinChild::class, 'walkin_child_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(WalkinFamily::class, 'walkin_family_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function statusLabel(): string
    {
        return $this->status === 'checked_out' ? 'Checked out' : 'Inside';
    }
}
