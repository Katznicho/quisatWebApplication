<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ChurchService extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'schedule_type',
        'weekday',
        'service_date',
        'starts_at',
        'ends_at',
        'is_active',
        'timezone',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'service_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function occursOn(string $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $day = Carbon::parse($date);
        if ($this->schedule_type === 'date') {
            return optional($this->service_date)->toDateString() === $day->toDateString();
        }

        return (int) $this->weekday === $day->dayOfWeek;
    }

    public function timeLabel(): string
    {
        $start = Carbon::parse($this->starts_at)->format('g:i a');
        $end = Carbon::parse($this->ends_at)->format('g:i a');

        return $start.' – '.$end;
    }

    public function scheduleLabel(): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        if ($this->schedule_type === 'date') {
            $when = optional($this->service_date)->format('D, j M Y') ?: 'Date';

            return $when.', '.$this->timeLabel();
        }

        return ($days[(int) $this->weekday] ?? 'Weekly').', '.$this->timeLabel();
    }

    public function choiceLabel(): string
    {
        return $this->name.' · '.$this->scheduleLabel();
    }

    public function nextDate(string $from): string
    {
        $start = Carbon::parse($from)->startOfDay();
        if ($this->schedule_type === 'date') {
            return optional($this->service_date)->toDateString() ?: $start->toDateString();
        }

        $cursor = $start->copy();
        for ($i = 0; $i < 7; $i++) {
            if ($cursor->dayOfWeek === (int) $this->weekday) {
                return $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $start->toDateString();
    }
}
