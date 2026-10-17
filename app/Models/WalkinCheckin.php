<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalkinCheckin extends Model
{
    protected $fillable = [
        'business_id',
        'walkin_family_id',
        'church_service_id',
        'service_date',
        'client_token',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(WalkinFamily::class, 'walkin_family_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(WalkinVisit::class);
    }
}
