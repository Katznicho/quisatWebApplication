<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClassAssignment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'branch_id',
        'class_room_id',
        'subject_id',
        'teacher_id',
        'title',
        'description',
        'assignment_type',
        'assigned_date',
        'due_date',
        'due_time',
        'total_marks',
        'attachments',
        'status',
        'published_at',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
        'due_time' => 'datetime:H:i',
        'attachments' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (self $assignment) {
            if (!$assignment->uuid) {
                $assignment->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
