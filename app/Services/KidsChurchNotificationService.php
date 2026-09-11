<?php

namespace App\Services;

use App\Models\KidsIncident;
use App\Models\KidsLesson;
use App\Models\ParentGuardian;
use App\Models\PickupCode;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KidsChurchNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyPickupCode(PickupCode $code): void
    {
        $code->loadMissing('student.parentGuardian');
        $parent = $code->student?->parentGuardian;
        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $child = $code->student?->first_name ?: 'your child';
        $this->notify(
            collect([$parent]),
            'Pickup code for '.$child,
            'Show this 4-digit code at pickup: '.$code->code,
            [
                'type' => 'pickup',
                'screen' => 'CheckInOut',
                'student_id' => (string) $code->student_id,
                'pickup_code' => $code->code,
            ]
        );
    }

    public function notifyLesson(KidsLesson $lesson): void
    {
        $recipients = $this->parentsForLesson($lesson);
        if ($recipients->isEmpty()) {
            return;
        }

        $label = match ($lesson->type) {
            'home_resource' => 'Family faith resource',
            'pastor_devotional' => 'Pastor’s weekly devotion',
            default => 'This week’s lesson',
        };

        $this->notify(
            $recipients,
            $label.': '.$lesson->title,
            Str::limit($lesson->family_challenge ?: $lesson->body ?: $lesson->memory_verse ?: 'Open Quisat to read this week’s material.', 240),
            [
                'type' => 'lesson',
                'screen' => 'FaithLessons',
                'lesson_uuid' => (string) $lesson->uuid,
            ]
        );
    }

    public function notifyIncident(KidsIncident $incident): void
    {
        $incident->loadMissing('student.parentGuardian');
        $parent = $incident->student?->parentGuardian;
        if (! $parent instanceof ParentGuardian) {
            return;
        }

        $child = $incident->student?->first_name ?: 'your child';
        $this->notify(
            collect([$parent]),
            'Care update for '.$child,
            Str::limit($incident->title.': '.$incident->description, 240),
            [
                'type' => 'incident',
                'screen' => 'IncidentReports',
                'incident_uuid' => (string) $incident->uuid,
                'student_id' => (string) $incident->student_id,
            ]
        );

        $incident->update(['notified_parent_at' => now()]);
    }

    protected function parentsForLesson(KidsLesson $lesson): Collection
    {
        $query = Student::query()
            ->where('business_id', $lesson->business_id)
            ->whereNotNull('parent_guardian_id');

        if ($lesson->class_room_id) {
            $query->where('class_room_id', $lesson->class_room_id);
        }

        $parentIds = $query->pluck('parent_guardian_id')->unique()->filter();

        return ParentGuardian::query()->whereIn('id', $parentIds)->get();
    }

    protected function notify(Collection $recipients, string $title, string $body, array $data): void
    {
        foreach ($recipients as $recipient) {
            UserNotification::create([
                'notifiable_type' => $recipient::class,
                'notifiable_id' => $recipient->getKey(),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        $tokens = $this->resolveDeviceTokens($recipients);
        if ($tokens->isNotEmpty()) {
            $this->pushService->sendExpoBatch($tokens, $title, $body, $data);
        }
    }
}
