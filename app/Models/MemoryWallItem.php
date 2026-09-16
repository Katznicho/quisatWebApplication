<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MemoryWallItem extends Model
{
    protected $fillable = [
        'uuid',
        'business_id',
        'created_by',
        'type',
        'title',
        'body',
        'scripture_ref',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCurrent($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today);
            });
    }

    public static function visibleForBusinesses(array $businessIds)
    {
        $ids = collect($businessIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $items = static::query()
            ->whereIn('business_id', $ids)
            ->current()
            ->latest()
            ->get();

        if ($items->isNotEmpty()) {
            return $items;
        }

        return static::query()
            ->whereIn('business_id', $ids)
            ->where('is_active', true)
            ->latest()
            ->get();
    }

    public static function verseForBusinesses(array $businessIds): ?self
    {
        return static::visibleForBusinesses($businessIds)->firstWhere('type', 'memory_verse');
    }
}
