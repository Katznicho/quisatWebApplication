<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'feature_ids',

    ];

    //cast
    protected $casts = [
        'feature_ids' => 'array',
    ];

    //a business category has many businesses
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function documentTypes()
    {
        return $this->belongsToMany(DocumentType::class, 'business_category_document_type')
            ->withPivot(['is_required', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('document_types.name');
    }

    public function requiredDocumentTypesForAccount(string $accountType = 'business')
    {
        return $this->documentTypes()
            ->where('document_types.is_active', true)
            ->where(function ($query) use ($accountType) {
                $query->where('document_types.account_type', $accountType)
                    ->orWhere('document_types.account_type', 'both');
            })
            ->get();
    }

    protected static function booted()
    {
        static::creating(function ($category) {
            $category->uuid = (string) Str::uuid();
        });

        // When category feature_ids change, only drop features that were removed from
        // this category. Extra features (e.g. school modules on a church tenant) stay.
        static::updating(function (BusinessCategory $category) {
            if (! $category->isDirty('feature_ids')) {
                return;
            }

            $oldFeatureIds = array_map('intval', $category->getOriginal('feature_ids') ?? []);
            $newFeatureIds = array_map('intval', $category->feature_ids ?? []);
            $removedIds = array_values(array_diff($oldFeatureIds, $newFeatureIds));

            if ($removedIds === []) {
                return;
            }

            $category->businesses()->each(function (Business $business) use ($removedIds) {
                $enabledIds = array_map('intval', $business->enabled_feature_ids ?? []);
                $filteredIds = array_values(array_diff($enabledIds, $removedIds));

                if ($filteredIds !== $enabledIds) {
                    $business->enabled_feature_ids = $filteredIds;
                    $business->saveQuietly();
                }
            });
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
