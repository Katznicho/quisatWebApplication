<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrayerRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'parent_guardian_id',
        'student_id',
        'body',
        'is_anonymous',
        'status',
        'praise_report',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
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

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
