<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BroadcastAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'sender_id',
        'title',
        'content',
        'type',
        'channels',
        'target_roles',
        'target_users',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'target_roles' => 'array',
        'target_users' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
