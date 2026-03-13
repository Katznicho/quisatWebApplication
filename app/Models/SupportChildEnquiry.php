<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportChildEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_child_id',
        'full_name',
        'email',
        'phone',
        'preferred_contact_method',
        'message',
        'source',
    ];

    public function child()
    {
        return $this->belongsTo(SupportChild::class, 'support_child_id');
    }
}

