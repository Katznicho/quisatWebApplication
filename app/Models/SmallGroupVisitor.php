<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupVisitor extends Model
{
    protected $fillable = [
        'small_group_meeting_id',
        'small_group_id',
        'business_id',
        'visitor_name',
        'guardian_name',
        'contact',
        'created_by',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SmallGroupMeeting::class, 'small_group_meeting_id');
    }
}
