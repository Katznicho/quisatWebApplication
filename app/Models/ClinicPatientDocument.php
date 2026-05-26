<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClinicPatientDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'business_id',
        'clinic_patient_id',
        'uploaded_by',
        'type',
        'title',
        'description',
        'file_path',
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

    protected static function booted(): void
    {
        static::creating(function (ClinicPatientDocument $document) {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function patient()
    {
        return $this->belongsTo(ClinicPatient::class, 'clinic_patient_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        return asset('storage/'.ltrim($this->file_path, '/'));
    }
}
