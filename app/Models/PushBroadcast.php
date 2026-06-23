<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PushBroadcast extends Model
{
    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_PARENTS = 'parents';

    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCE_BUSINESS = 'business';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'title',
        'body',
        'data',
        'audience',
        'business_id',
        'channels',
        'status',
        'created_by',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'push_sent_count',
        'push_failed_count',
        'in_app_count',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'channels' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PushBroadcast $broadcast) {
            if (empty($broadcast->uuid)) {
                $broadcast->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function sendsPush(): bool
    {
        return in_array('push', $this->channels ?? [], true);
    }

    public function sendsInApp(): bool
    {
        return in_array('in_app', $this->channels ?? [], true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
