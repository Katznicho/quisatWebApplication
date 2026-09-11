<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuisatAlbumLike extends Model
{
    protected $fillable = [
        'album_id',
        'media_id',
        'parent_guardian_id',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(QuisatAlbum::class, 'album_id');
    }

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }
}
