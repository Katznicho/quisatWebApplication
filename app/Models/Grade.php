<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Grade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'student_id',
        'exam_id',
        'subject_id',
        'class_room_id',
        'term_id',
        'marks_obtained',
        'percentage',
        'grade_letter',
        'remarks',
        'status',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($grade) {
            $grade->uuid = Str::uuid();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
