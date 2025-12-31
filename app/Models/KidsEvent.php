<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KidsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'host_organization',
        'category',
        'location',
        'price',
        'max_participants',
        'current_participants',
        'start_date',
        'end_date',
        'status',
        'requires_parent_permission',
        'image_url',
        'target_age_groups',
        'requirements',
        'contact_info',
        'contact_email',
        'contact_phone',
        'rating',
        'total_ratings',
        'is_featured',
        'is_external',
        'business_id',
        'created_by',
        'registration_method',
        'registration_link',
        'registration_list',
        'social_media_handles',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'organizer_address',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'target_age_groups' => 'array',
        'requirements' => 'array',
        'requires_parent_permission' => 'boolean',
        'is_featured' => 'boolean',
        'is_external' => 'boolean',
        'registration_list' => 'array',
        'social_media_handles' => 'array',
    ];

    // Relationships
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getSpotsAvailableAttribute(): int
    {
        if (!$this->max_participants) {
            return 999; // Unlimited
        }
        return max(0, $this->max_participants - $this->current_participants);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->max_participants && $this->current_participants >= $this->max_participants;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->price > 0 ? '$' . number_format($this->price, 2) : 'Free';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'published' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
                    ->where('start_date', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
                    ->orWhere('end_date', '<', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
