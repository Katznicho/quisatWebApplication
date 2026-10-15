<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchVisitorAttendance extends Model
{
    protected $fillable = [
        'registration_id',
        'business_id',
        'church_visitor_id',
        'class_room_id',
        'service_name',
        'service_date',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'collector_name',
    ];

    protected $casts = [
        'service_date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ChurchVisitorRegistration::class, 'registration_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(ChurchVisitor::class, 'church_visitor_id');
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
}
