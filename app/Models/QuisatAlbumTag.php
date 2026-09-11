<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuisatAlbumTag extends Model
{
    protected $fillable = [
        'media_id',
        'student_id',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(QuisatAlbumMedia::class, 'media_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
