<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChurchVisitorRegistration extends Model
{
    protected $fillable = [
        'church_visitor_id',
        'business_id',
        'church_service_id',
        'service_date',
        'service_name',
        'status',
        'class_room_id',
        'registered_by_parent_id',
        'pickup_code',
        'pickup_used_at',
        'active_key',
        'client_token',
        'cancelled_at',
        'cancelled_by_parent_id',
    ];

    protected $casts = [
        'service_date' => 'date',
        'pickup_used_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(ChurchVisitor::class, 'church_visitor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ChurchService::class, 'church_service_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'registered_by_parent_id');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(ChurchVisitorAttendance::class, 'registration_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled' => 'Cancelled',
            default => 'Awaiting church check-in',
        };
    }
}
