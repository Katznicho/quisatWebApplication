<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CalendarEventNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyPublished(CalendarEvent $event): void
    {
        if (($event->status ?? '') !== 'published') {
            return;
        }

        $recipients = $this->resolveRecipients($event);
        if ($recipients->isEmpty()) {
            return;
        }

        $when = $event->start_date?->format('M j, Y g:i A') ?? 'soon';
        $title = 'School event';
        $body = Str::limit(trim("{$event->title} · {$when}"), 240);

        $data = [
            'type' => 'event',
            'screen' => 'SchoolEvents',
            'event_id' => (string) $event->id,
            'event_uuid' => (string) ($event->uuid ?? ''),
            'title' => $title,
        ];

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
        if ($tokens->isEmpty()) {
            return;
        }

        $this->pushService->sendExpoBatch($tokens, $title, $body, $data);
    }

    /**
     * @return Collection<int, Model>
     */
    protected function resolveRecipients(CalendarEvent $event): Collection
    {
        $businessId = (int) $event->business_id;

        $parents = ParentGuardian::query()
            ->where('status', 'active')
            ->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)
                    ->orWhereHas('memberships', function ($membershipQuery) use ($businessId) {
                        $membershipQuery
                            ->where('business_id', $businessId)
                            ->where('status', 'active');
                    });
            })
            ->get();

        $staff = User::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->get();

        return $this->collapseLinkedOwners($parents->merge($staff));
    }
}
