<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssignmentNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyPublished(ClassAssignment $assignment): void
    {
        if (($assignment->status ?? '') !== 'published') {
            return;
        }

        $assignment->loadMissing(['classRoom', 'subject', 'teacher']);

        $parents = $this->resolveParents($assignment);
        if ($parents->isEmpty()) {
            return;
        }

        $className = $assignment->classRoom?->name ?? 'your class';
        $subject = $assignment->subject?->name;
        $due = $assignment->due_date?->format('M j, Y');
        $title = 'New assignment';
        $bodyParts = [
            $assignment->title,
            "for {$className}",
        ];
        if ($subject) {
            $bodyParts[] = "({$subject})";
        }
        if ($due) {
            $bodyParts[] = "Due {$due}";
        }
        $body = Str::limit(implode(' ', $bodyParts), 240);

        $data = [
            'type' => 'assignment',
            'screen' => 'MyAssignments',
            'assignment_id' => (string) $assignment->id,
            'assignment_uuid' => (string) $assignment->uuid,
            'class_room_id' => (string) $assignment->class_room_id,
            'title' => $title,
        ];

        foreach ($parents as $parent) {
            UserNotification::create([
                'notifiable_type' => $parent::class,
                'notifiable_id' => $parent->getKey(),
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        $tokens = $this->resolveDeviceTokens($parents);
        if ($tokens->isEmpty()) {
            return;
        }

        $this->pushService->sendExpoBatch($tokens, $title, $body, $data);
    }

    /**
     * @return Collection<int, ParentGuardian>
     */
    protected function resolveParents(ClassAssignment $assignment): Collection
    {
        return Student::query()
            ->where('business_id', $assignment->business_id)
            ->where('class_room_id', $assignment->class_room_id)
            ->where('status', 'active')
            ->whereNotNull('parent_guardian_id')
            ->with('parentGuardian')
            ->get()
            ->pluck('parentGuardian')
            ->filter(fn ($parent) => $parent instanceof ParentGuardian)
            ->unique(fn (ParentGuardian $parent) => $parent->getKey())
            ->values();
    }
}
