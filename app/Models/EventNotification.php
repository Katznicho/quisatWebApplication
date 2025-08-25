<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EventNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'calendar_event_id',
        'business_id',
        'title',
        'message',
        'notification_type',
        'status',
        'target_type',
        'target_ids',
        'target_filters',
        'scheduled_at',
        'sent_at',
        'reminder_minutes',
        'total_recipients',
        'sent_count',
        'failed_count',
        'delivery_log',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'target_filters' => 'array',
        'delivery_log' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($eventNotification) {
            $eventNotification->uuid = Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    // Relationships
    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at');
    }

    // Helper methods
    public function getRecipientsAttribute()
    {
        switch ($this->target_type) {
            case 'all':
                return $this->getAllRecipients();
            case 'specific_users':
                return $this->getSpecificUsers();
            case 'specific_roles':
                return $this->getUsersByRoles();
            case 'specific_classes':
                return $this->getUsersByClasses();
            case 'specific_students':
                return $this->getSpecificStudents();
            case 'specific_teachers':
                return $this->getSpecificTeachers();
            case 'specific_parents':
                return $this->getSpecificParents();
            default:
                return collect();
        }
    }

    private function getAllRecipients()
    {
        return User::where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    private function getSpecificUsers()
    {
        if (!$this->target_ids) return collect();
        
        return User::whereIn('id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    private function getUsersByRoles()
    {
        if (!$this->target_ids) return collect();
        
        return User::whereIn('role_id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    private function getUsersByClasses()
    {
        if (!$this->target_ids) return collect();
        
        // Get students in specific classes
        $studentIds = Student::whereIn('class_room_id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->pluck('id');
            
        // Get teachers of those classes
        $teacherIds = Timetable::whereIn('class_room_id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->pluck('teacher_id');
            
        return User::whereIn('id', $studentIds->merge($teacherIds))
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    private function getSpecificStudents()
    {
        if (!$this->target_ids) return collect();
        
        $studentIds = Student::whereIn('id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->pluck('id');
            
        return User::whereIn('id', $studentIds)
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    private function getSpecificTeachers()
    {
        if (!$this->target_ids) return collect();
        
        return User::whereIn('id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->whereHas('role', function($query) {
                $query->where('name', 'Staff');
            })
            ->get();
    }

    private function getSpecificParents()
    {
        if (!$this->target_ids) return collect();
        
        $parentIds = ParentGuardian::whereIn('id', $this->target_ids)
            ->where('business_id', $this->business_id)
            ->pluck('id');
            
        return User::whereIn('id', $parentIds)
            ->where('business_id', $this->business_id)
            ->where('status', 'active')
            ->get();
    }

    public function isScheduled()
    {
        return !is_null($this->scheduled_at);
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function getSuccessRateAttribute()
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }
}
