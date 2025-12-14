<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignmentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submission_file_path',
        'submission_file_name',
        'submission_file_size',
        'submission_mime_type',
        'notes',
        'status',
        'marks_obtained',
        'feedback',
        'submitted_at',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'marks_obtained' => 'integer',
        'submission_file_size' => 'integer',
    ];

    public function assignment()
    {
        return $this->belongsTo(ClassAssignment::class, 'assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
