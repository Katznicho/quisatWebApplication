<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudentDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'student_id',
        'uploaded_by',
        'type',
        'title',
        'description',
        'file_path',
        'file_url',
        'mime_type',
        'size',
        'meta',
    ];

    protected $casts = [
        'size' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (StudentDocument $document) {
            if (empty($document->uuid)) {
                $document->uuid = Str::uuid();
            }
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

