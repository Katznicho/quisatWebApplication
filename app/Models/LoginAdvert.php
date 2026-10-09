<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LoginAdvert extends Model
{
    protected $fillable = [
        'advertiser_name',
        'logo_path',
        'creative_type',
        'creative_path',
        'destination_url',
        'is_active',
        'starts_at',
        'ends_at',
        'timezone',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeScheduled(Builder $query, ?Carbon $moment = null): Builder
    {
        $moment ??= now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $moment)
            ->where('ends_at', '>=', $moment);
    }

    public function scheduleLabel(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return 'Scheduled';
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return 'Ended';
        }

        return 'Active';
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public function toAppPayload(): array
    {
        return [
            'id' => $this->id,
            'advertiser_name' => $this->advertiser_name,
            'logo_url' => $this->mediaUrl($this->logo_path),
            'creative_type' => $this->creative_type,
            'creative_url' => $this->mediaUrl($this->creative_path),
            'destination_url' => $this->destination_url,
            'duration_seconds' => 10,
        ];
    }
}
