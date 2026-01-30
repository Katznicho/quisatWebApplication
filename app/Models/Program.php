<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'age-group',
        'status',
        'image',
        'video',
        'social_media_handles',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'program_ids' => 'array',
        'social_media_handles' => 'array',
    ];

    // A program can have many events
    public function events()
    {
        return $this->hasMany(ProgramEvent::class, 'program_ids');
    }

    // Get events for this program
    public function getEventsAttribute()
    {
        return ProgramEvent::whereJsonContains('program_ids', $this->id)->get();
    }

    // Get total events count
    public function getTotalEventsAttribute()
    {
        return ProgramEvent::whereJsonContains('program_ids', $this->id)->count();
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName(){
        return 'uuid';
    }
}
