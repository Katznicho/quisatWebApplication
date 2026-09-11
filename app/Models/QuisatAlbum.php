<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QuisatAlbum extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'created_by',
        'title',
        'description',
        'type',
        'class_room_id',
        'calendar_event_id',
        'status',
        'is_hd_paid',
        'hd_price',
        'published_at',
    ];

    protected $casts = [
        'is_hd_paid' => 'boolean',
        'hd_price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $album) {
            if (empty($album->uuid)) {
                $album->uuid = (string) Str::uuid();
            }
            if ($album->status === 'published' && empty($album->published_at)) {
                $album->published_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuisatAlbumMedia::class, 'album_id')->orderBy('sort_order');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(QuisatAlbumLike::class, 'album_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuisatAlbumComment::class, 'album_id')->latest();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
