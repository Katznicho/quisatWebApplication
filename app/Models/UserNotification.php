<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class UserNotification extends Model
{
    protected $fillable = [
        'uuid',
        'notifiable_type',
        'notifiable_id',
        'push_broadcast_id',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserNotification $notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = (string) Str::uuid();
            }
        });
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(PushBroadcast::class, 'push_broadcast_id');
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public static function unreadCountFor(?string $type, $id): int
    {
        if (! $type || $id === null || $id === '') {
            return 0;
        }

        return static::query()
            ->where('notifiable_type', $type)
            ->where('notifiable_id', $id)
            ->whereNull('read_at')
            ->count();
    }

    public function deduplicationKey(): string
    {
        if ($this->push_broadcast_id) {
            return 'broadcast:'.$this->push_broadcast_id;
        }

        $data = $this->data ?? [];

        foreach (['broadcast_id', 'message_id', 'announcement_id', 'assignment_id', 'event_id', 'fee_id'] as $field) {
            if (! empty($data[$field])) {
                return $field.':'.$data[$field];
            }
        }

        $created = $this->created_at?->format('Y-m-d H:i') ?? '';

        return md5($this->title.'|'.$this->body.'|'.$created);
    }
}
