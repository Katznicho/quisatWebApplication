<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Term extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'created_by',
        'name',
        'code',
        'description',
        'academic_year',
        'academic_year_start',
        'academic_year_end',
        'start_date',
        'end_date',
        'registration_start_date',
        'registration_end_date',
        'term_type',
        'duration_weeks',
        'total_instructional_days',
        'total_instructional_hours',
        'is_grading_period',
        'is_exam_period',
        'mid_term_start_date',
        'mid_term_end_date',
        'final_exam_start_date',
        'final_exam_end_date',
        'status',
        'is_current_term',
        'is_next_term',
        'tuition_fee',
        'other_fees',
        'fee_due_date',
        'late_fee_applicable',
        'late_fee_amount',
        'late_fee_days',
        'holidays',
        'special_events',
        'notes',
        'announcements',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
        'mid_term_start_date' => 'date',
        'mid_term_end_date' => 'date',
        'final_exam_start_date' => 'date',
        'final_exam_end_date' => 'date',
        'fee_due_date' => 'date',
        'is_grading_period' => 'boolean',
        'is_exam_period' => 'boolean',
        'is_current_term' => 'boolean',
        'is_next_term' => 'boolean',
        'late_fee_applicable' => 'boolean',
        'tuition_fee' => 'decimal:2',
        'other_fees' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'holidays' => 'array',
        'special_events' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($term) {
            if (empty($term->uuid)) {
                $term->uuid = Str::uuid();
            }
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

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCurrentTerm()
    {
        return $this->is_current_term;
    }

    public function isNextTerm()
    {
        return $this->is_next_term;
    }

    public function isRegistrationOpen()
    {
        if (!$this->registration_start_date || !$this->registration_end_date) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->registration_start_date, $this->registration_end_date);
    }

    public function isInProgress()
    {
        $now = Carbon::now();
        return $now->between($this->start_date, $this->end_date);
    }

    public function isUpcoming()
    {
        return Carbon::now()->lt($this->start_date);
    }

    public function isPast()
    {
        return Carbon::now()->gt($this->end_date);
    }

    public function getDurationInDays()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getRemainingDays()
    {
        if ($this->isPast()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->end_date, false);
    }

    public function getProgressPercentage()
    {
        if ($this->isPast()) {
            return 100;
        }

        if ($this->isUpcoming()) {
            return 0;
        }

        $totalDays = $this->getDurationInDays();
        $elapsedDays = Carbon::now()->diffInDays($this->start_date, false);
        
        return min(100, max(0, round(($elapsedDays / $totalDays) * 100, 2)));
    }

    public function getTotalFees()
    {
        return ($this->tuition_fee ?? 0) + ($this->other_fees ?? 0);
    }

    public function getLateFeeAmount()
    {
        if (!$this->late_fee_applicable || !$this->late_fee_amount) {
            return 0;
        }

        return $this->late_fee_amount;
    }

    public function isLateFeeApplicable()
    {
        if (!$this->late_fee_applicable || !$this->fee_due_date) {
            return false;
        }

        return Carbon::now()->gt($this->fee_due_date);
    }

    public function getLateFeeDays()
    {
        if (!$this->isLateFeeApplicable()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->fee_due_date, false);
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'active' => 'bg-green-100 text-green-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getTermTypeLabel()
    {
        return match($this->term_type) {
            'first' => 'First Term',
            'second' => 'Second Term',
            'third' => 'Third Term',
            'summer' => 'Summer Term',
            'holiday' => 'Holiday Term',
            'other' => 'Other Term',
            default => ucfirst($this->term_type),
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current_term', true);
    }

    public function scopeNext($query)
    {
        return $query->where('is_next_term', true);
    }

    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', Carbon::now());
    }

    public function scopeInProgress($query)
    {
        $now = Carbon::now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeCompleted($query)
    {
        return $query->where('end_date', '<', Carbon::now());
    }
}
