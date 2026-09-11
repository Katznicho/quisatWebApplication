<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KidsIncident extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'student_id',
        'reported_by',
        'type',
        'title',
        'description',
        'action_taken',
        'notified_parent_at',
    ];

    protected $casts = [
        'notified_parent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $incident) {
            if (empty($incident->uuid)) {
                $incident->uuid = (string) Str::uuid();
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
