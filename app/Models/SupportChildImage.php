<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportChildImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_child_id',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function supportChild()
    {
        return $this->belongsTo(SupportChild::class);
    }
}
