<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'account_type',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function businessCategories(): BelongsToMany
    {
        return $this->belongsToMany(BusinessCategory::class, 'business_category_document_type')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps();
    }

    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(BusinessRegistrationDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function accountTypeLabel(): string
    {
        return match ($this->account_type) {
            'individual' => 'Individual',
            'both' => 'Both',
            default => 'Business',
        };
    }
}
