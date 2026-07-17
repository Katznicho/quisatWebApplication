<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentGuardianBusiness extends Model
{
    protected $table = 'parent_guardian_business';

    protected $fillable = [
        'parent_guardian_id',
        'business_id',
        'relationship',
        'joined_via',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parentGuardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_guardian_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
