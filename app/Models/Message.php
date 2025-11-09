<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'attachment_path',
        'attachment_name',
        'attachment_size',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (self $message) {
            if (!$message->uuid) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
