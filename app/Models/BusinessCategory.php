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

    protected static function booted()
    {
        static::creating(function ($category) {
            $category->uuid = (string) Str::uuid();
        });

        // When category feature_ids are updated, sync businesses' enabled_feature_ids
        static::updated(function ($category) {
            if ($category->isDirty('feature_ids')) {
                $newFeatureIds = $category->feature_ids ?? [];
                $newFeatureIds = array_map('intval', $newFeatureIds);
                
                // Update all businesses in this category to remove features not in category
                $category->businesses()->each(function ($business) use ($newFeatureIds) {
                    $enabledIds = $business->enabled_feature_ids ?? [];
                    $enabledIds = array_map('intval', $enabledIds);
                    
                    // Keep only features that are still in the category
                    $filteredIds = array_intersect($enabledIds, $newFeatureIds);
                    
                    // Only update if there's a change
                    if (count($filteredIds) !== count($enabledIds)) {
                        $business->enabled_feature_ids = array_values($filteredIds);
                        $business->saveQuietly(); // Use saveQuietly to avoid triggering observers
                    }
                });
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
