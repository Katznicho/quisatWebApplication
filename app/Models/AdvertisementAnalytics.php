<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisementAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertisement_id',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'user_id', // For tracking which user interacted
        'interaction_type' // 'view', 'click', 'conversion'
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2'
    ];

    // Relationships
    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getClickThroughRate()
    {
        return $this->impressions > 0 ? round(($this->clicks / $this->impressions) * 100, 2) : 0;
    }

    public function getConversionRate()
    {
        return $this->clicks > 0 ? round(($this->conversions / $this->clicks) * 100, 2) : 0;
    }
}




