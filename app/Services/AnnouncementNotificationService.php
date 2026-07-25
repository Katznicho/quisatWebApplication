<?php

namespace App\Services;

use App\Models\BroadcastAnnouncement;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnnouncementNotificationService
{
    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function dispatch(BroadcastAnnouncement $announcement): void
    {
        if ($announcement->status !== 'published') {
            return;
        }

        $channels = $announcement->channels ?? ['in_app'];
        $recipients = $this->resolveRecipients($announcement);

        if ($recipients->isEmpty()) {
            return;
        }

        $body = Str::limit(strip_tags((string) $announcement->content), 240);
        $data = [
            'type' => 'announcement',
            'announcement_id' => $announcement->id,
            'business_id' => $announcement->business_id,
        ];

        if (in_array('in_app', $channels, true)) {
            foreach ($recipients as $recipient) {
                UserNotification::create([
                    'notifiable_type' => $recipient::class,
                    'notifiable_id' => $recipient->getKey(),
                    'title' => $announcement->title,
                    'body' => $body,
                    'data' => $data,
                ]);
            }
        }

        if (! in_array('push', $channels, true)) {
            return;
        }

        $tokens = $this->resolveDeviceTokens($recipients);

        if ($tokens->isEmpty()) {
            return;
        }

        $this->pushService->sendExpoBatch(
            $tokens,
            $announcement->title,
            $body,
            $data
        );
    }

    /**
     * @return Collection<int, Model>
     */
    protected function resolveRecipients(BroadcastAnnouncement $announcement): Collection
    {
        $businessId = (int) $announcement->business_id;
        $roles = $announcement->target_roles ?? ['all_users'];
        $targetUserIds = collect($announcement->target_users ?? [])->filter()->map(fn ($id) => (int) $id);

        $includeParents = in_array('all_users', $roles, true) || in_array('parents', $roles, true);
        $includeStaff = in_array('all_users', $roles, true) || in_array('staff', $roles, true);
        $includeStudents = in_array('all_users', $roles, true) || in_array('students', $roles, true);

        $recipients = collect();

        if ($includeParents) {
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

            $recipients = $recipients->merge($parents);
        }

        if ($includeStaff || $includeStudents) {
            $staffQuery = User::query()
                ->where('business_id', $businessId)
                ->where('status', 'active');

            if ($targetUserIds->isNotEmpty()) {
                $staffQuery->whereIn('id', $targetUserIds);
            }

            $recipients = $recipients->merge($staffQuery->get());
        }

        return $recipients
            ->unique(fn (Model $owner) => $owner::class.'#'.$owner->getKey())
            ->values();
    }

    /**
     * @param  Collection<int, Model>  $recipients
     * @return Collection<int, DeviceToken>
     */
    protected function resolveDeviceTokens(Collection $recipients): Collection
    {
        return DeviceToken::query()
            ->where('is_active', true)
            ->where(function ($query) use ($recipients) {
                foreach ($recipients as $recipient) {
                    $query->orWhere(function ($ownerQuery) use ($recipient) {
                        $ownerQuery
                            ->where('tokenable_type', $recipient::class)
                            ->where('tokenable_id', $recipient->getKey());
                    });
                }
            })
            ->get();
    }
}
