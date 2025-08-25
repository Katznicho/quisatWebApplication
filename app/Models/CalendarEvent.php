<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'created_by',
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'color',
        'event_type',
        'priority',
        'is_all_day',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_days',
        'recurrence_end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_all_day' => 'boolean',
        'is_recurring' => 'boolean',
        'recurrence_days' => 'array',
        'recurrence_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($calendarEvent) {
            $calendarEvent->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
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

    public function notifications()
    {
        return $this->hasMany(EventNotification::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Helper methods
    public function isUpcoming()
    {
        return $this->start_date >= now();
    }

    public function isToday()
    {
        return $this->start_date->isToday();
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInMinutes($this->end_date);
    }

    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }
}
