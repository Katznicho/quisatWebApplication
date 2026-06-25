<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model for the product + shop reviews union used in the admin feedback table.
 */
class MarketplaceReview extends Model
{
    protected $table = 'marketplace_reviews';

    public $timestamps = false;

    protected $guarded = [];
}
