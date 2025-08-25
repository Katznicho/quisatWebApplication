<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'business_id',
        'subject_id',
        'class_room_id',
        'term_id',
        'exam_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'exam_type',
        'status',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_marks' => 'integer',
        'passing_marks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($exam) {
            $exam->uuid = Str::uuid();
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
