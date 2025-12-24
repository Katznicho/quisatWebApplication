<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'address',
        'logo',
        'percentage_charge',
        'minimum_amount',
        'type',
        'account_number',
        'account_balance',
        'mode',
        'date',
        'country',
        'city',
        'business_category_id',
        'enabled_feature_ids'
    ];

    //cast
    protected $casts = [
        'enabled_feature_ids' => 'array',
    ];

    /**
     * Mutator to ensure enabled_feature_ids is always an array of integers
     */
    public function setEnabledFeatureIdsAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['enabled_feature_ids'] = json_encode([]);
        } else {
            // Ensure all values are integers
            $ids = is_array($value) ? $value : json_decode($value, true) ?? [];
            $ids = array_map('intval', array_filter($ids));
            $this->attributes['enabled_feature_ids'] = json_encode(array_values($ids));
        }
    }

    // a businness has many users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    //a business has many transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    //a business has many business categories
    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Check if a business has a specific feature enabled
     */
    public function hasFeature($featureId)
    {
        if (!$this->enabled_feature_ids) {
            return false;
        }
        
        // Ensure both are compared as integers to avoid type mismatch
        $enabledIds = array_map('intval', $this->enabled_feature_ids);
        $featureId = (int) $featureId;
        
        return in_array($featureId, $enabledIds, true);
    }

    /**
     * Check if a business has a feature by name
     * Returns true if:
     * 1. The feature exists (not soft-deleted)
     * 2. The feature is enabled for this business (in enabled_feature_ids)
     * 
     * Note: If a feature is enabled for the business, it will show even if not in the category.
     * The category's feature_ids is used for filtering available features in the edit form,
     * but doesn't restrict what can be displayed in the sidebar.
     */
    public function hasFeatureByName($featureName)
    {
        // Only get non-soft-deleted features
        $feature = \App\Models\Feature::where('name', $featureName)->first();
        
        if (!$feature) {
            return false;
        }
        
        // Check if feature is enabled for this business
        // If it's enabled, show it regardless of category restrictions
        return $this->hasFeature($feature->id);
    }

   
}
