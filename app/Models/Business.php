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
        
        return in_array($featureId, $this->enabled_feature_ids);
    }

    /**
     * Check if a business has a feature by name
     */
    public function hasFeatureByName($featureName)
    {
        $feature = \App\Models\Feature::where('name', $featureName)->first();
        
        if (!$feature) {
            return false;
        }
        
        return $this->hasFeature($feature->id);
    }

   
}
