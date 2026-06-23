<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessRegistrationDocument extends Model
{
    protected $fillable = [
        'business_id',
        'document_type_id',
        'file_path',
        'original_filename',
        'mime_type',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path || ! Storage::disk('public')->exists($this->file_path)) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        $mime = strtolower((string) ($this->mime_type ?? ''));

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $extension = strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function isPdf(): bool
    {
        $mime = strtolower((string) ($this->mime_type ?? ''));

        if ($mime === 'application/pdf') {
            return true;
        }

        return strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION)) === 'pdf';
    }
}
