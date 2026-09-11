<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KidsLesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'created_by',
        'class_room_id',
        'type',
        'title',
        'body',
        'object_lesson',
        'craft_supplies',
        'teaching_script',
        'memory_verse',
        'scripture_ref',
        'family_challenge',
        'video_url',
        'lesson_date',
        'status',
        'published_at',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $lesson) {
            if (empty($lesson->uuid)) {
                $lesson->uuid = (string) Str::uuid();
            }
            if (($lesson->status ?? 'published') === 'published' && empty($lesson->published_at)) {
                $lesson->published_at = now();
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

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
