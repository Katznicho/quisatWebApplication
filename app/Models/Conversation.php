<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'type',
        'business_id',
        'created_by',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime'
    ];

    protected static function booted()
    {
        static::creating(function ($conversation) {
            $conversation->uuid = (string) Str::uuid();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot(['joined_at', 'last_read_at', 'is_active'])
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getRouteKeyName()
    {
        return 'id';
    }

    // Helper methods
    public function isDirect()
    {
        return $this->type === 'direct';
    }

    public function isGroup()
    {
        return $this->type === 'group';
    }

    public function getOtherParticipant($userId)
    {
        if ($this->isDirect()) {
            return $this->users()->where('user_id', '!=', $userId)->first();
        }
        return null;
    }

    public function getDisplayName($userId)
    {
        if ($this->isDirect()) {
            $otherUser = $this->getOtherParticipant($userId);
            return $otherUser ? $otherUser->name : 'Unknown User';
        }
        return $this->title ?: 'Group Chat';
    }
}
