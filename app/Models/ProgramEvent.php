<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProgramEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'program_ids',
        'name',
        'description',
        'start_date',
        'end_date',
        'price',
        'status',
        'location',
        'currency_id',
        'business_id',
        'user_id',
        'image',
        'video',
        'registration_method',
        'registration_link',
        'registration_list',
        'social_media_handles',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'organizer_address',
    ];

    protected $casts = [
        'program_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'registration_list' => 'array',
        'social_media_handles' => 'array',
    ];

    // An event belongs to many programs
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_events', 'id', 'program_ids');
    }

    // An event has many attendees
    public function attendees()
    {
        return $this->hasMany(EventAttendee::class);
    }

    // An event belongs to a business
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // An event belongs to a user (creator)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // An event belongs to a currency
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // Get total attendees count
    public function getTotalAttendeesAttribute()
    {
        return $this->attendees()->count();
    }

    // Get confirmed attendees count
    public function getConfirmedAttendeesAttribute()
    {
        return $this->attendees()->where('status', 'confirmed')->count();
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
