<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuisatAlbumMedia extends Model
{
    protected $table = 'quisat_album_media';

    protected $fillable = [
        'uuid',
        'album_id',
        'uploaded_by',
        'path',
        'media_type',
        'caption',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(QuisatAlbum::class, 'album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(QuisatAlbumTag::class, 'media_id');
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with((string) $this->path, 'http')) {
            return $this->path;
        }

        return asset('storage/'.$this->path);
    }
}
