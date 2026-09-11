<?php

namespace App\Services;

use App\Models\ParentGuardian;
use App\Models\QuisatAlbum;
use App\Models\Student;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuisatAlbumNotificationService
{
    use ResolvesPushDeviceTokens;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function notifyPublished(QuisatAlbum $album): void
    {
        $album->loadMissing(['classRoom', 'media.tags']);
        $recipients = $this->parentsForAlbum($album);
        if ($recipients->isEmpty()) {
            return;
        }

        $count = $album->media()->count();
        $className = $album->classRoom?->name;
        $title = 'New Photos: '.$album->title;
        $body = $className
            ? "{$count} photos from {$className} are live."
            : "{$count} photos are live.";

        $data = [
            'type' => 'moments',
            'screen' => 'Moments',
            'album_id' => (string) $album->id,
            'album_uuid' => (string) $album->uuid,
        ];

        $this->notify($recipients, $title, Str::limit($body, 240), $data);
    }

    public function notifyTagged(QuisatAlbum $album, array $studentIds): void
    {
        $studentIds = array_values(array_unique(array_filter($studentIds)));
        if ($studentIds === []) {
            return;
        }

        $parents = ParentGuardian::query()
            ->whereIn('id', Student::query()->whereIn('id', $studentIds)->pluck('parent_guardian_id'))
            ->get();

        if ($parents->isEmpty()) {
            return;
        }

        $title = 'Your child was tagged';
        $body = 'Your child appears in photos from '.$album->title.'.';
        $data = [
            'type' => 'moments',
            'screen' => 'Moments',
            'album_id' => (string) $album->id,
            'album_uuid' => (string) $album->uuid,
        ];

        $this->notify($parents, $title, $body, $data);
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

    protected function parentsForAlbum(QuisatAlbum $album): Collection
    {
        $query = ParentGuardian::query()->where('status', 'active');

        if ($album->class_room_id) {
            $parentIds = Student::query()
                ->where('business_id', $album->business_id)
                ->where('class_room_id', $album->class_room_id)
                ->whereNotNull('parent_guardian_id')
                ->pluck('parent_guardian_id');

            return $query->whereIn('id', $parentIds)->get();
        }

        $businessId = (int) $album->business_id;

        return $query->where(function ($q) use ($businessId) {
            $q->where('business_id', $businessId)
                ->orWhereHas('memberships', function ($membership) use ($businessId) {
                    $membership->where('business_id', $businessId)->where('status', 'active');
                });
        })->get();
    }
}
