<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'student_id',
        'class_room_id',
        'term_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($attendance) {
            $attendance->uuid = Str::uuid();
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

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
