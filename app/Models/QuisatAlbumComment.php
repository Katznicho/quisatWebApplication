<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuisatAlbumComment extends Model
{
    protected $fillable = [
        'album_id',
        'media_id',
        'parent_guardian_id',
        'user_id',
        'body',
        'visible_to_staff_only',
    ];

    protected $casts = [
        'visible_to_staff_only' => 'boolean',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(QuisatAlbum::class, 'album_id');
    }

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
