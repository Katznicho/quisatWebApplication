<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'title',
        'description',
        'media_type',
        'media_path',
        'target_audience',
        'start_date',
        'end_date',
        'is_recurring',
        'recurrence_pattern',
        'status',
        'created_by',
        'budget',
        'category'
    ];

    protected $casts = [
        'target_audience' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_recurring' => 'boolean',
        'budget' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($advertisement) {
            $advertisement->uuid = (string) Str::uuid();
        });
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analytics()
    {
        return $this->hasMany(AdvertisementAnalytics::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled' && $this->start_date > now();
    }

    public function isExpired()
    {
        return $this->end_date < now();
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function getStatusBadgeColor()
    {
        return match($this->status) {
            'draft' => 'blue',
            'published' => 'green',
            default => 'gray'
        };
    }

    public function getTotalImpressions()
    {
        return $this->analytics()->sum('impressions');
    }

    public function getTotalClicks()
    {
        return $this->analytics()->sum('clicks');
    }

    public function getClickThroughRate()
    {
        $impressions = $this->getTotalImpressions();
        $clicks = $this->getTotalClicks();
        
        return $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
    }

    public function getTotalConversions()
    {
        return $this->analytics()->sum('conversions');
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
