<?php

namespace App\Services;

use App\Models\Business;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\PushBroadcast;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PushAudienceResolver
{
    /**
     * @return Collection<int, array{owner: Model, tokens: Collection<int, DeviceToken>}>
     */
    public function resolve(PushBroadcast $broadcast): Collection
    {
        $recipients = collect();

        foreach ($this->resolveOwners($broadcast) as $owner) {
            $tokens = DeviceToken::query()
                ->where('tokenable_type', $owner::class)
                ->where('tokenable_id', $owner->getKey())
                ->where('is_active', true)
                ->get();

            $recipients->push([
                'owner' => $owner,
                'tokens' => $tokens,
            ]);
        }

        return $recipients;
    }

    /**
     * @return Collection<int, Model>
     */
    protected function resolveOwners(PushBroadcast $broadcast): Collection
    {
        return match ($broadcast->audience) {
            PushBroadcast::AUDIENCE_PARENTS => $this->parentsQuery($broadcast)->get(),
            PushBroadcast::AUDIENCE_STAFF => $this->staffQuery($broadcast)->get(),
            PushBroadcast::AUDIENCE_BUSINESS => $this->businessAudience($broadcast),
            default => $this->parentsQuery($broadcast)->get()->merge($this->staffQuery($broadcast)->get()),
        };
    }

    protected function parentsQuery(PushBroadcast $broadcast)
    {
        $query = ParentGuardian::query()->where('status', 'active');

        if ($broadcast->business_id) {
            $query->where('business_id', $broadcast->business_id);
        }

        return $query;
    }

    protected function staffQuery(PushBroadcast $broadcast)
    {
        $query = User::query()->where('status', 'active');

        if ($broadcast->business_id) {
            $query->where('business_id', $broadcast->business_id);
        } else {
            $query->where('business_id', '!=', 1);
        }

        return $query;
    }

    protected function businessAudience(PushBroadcast $broadcast): Collection
    {
        if (! $broadcast->business_id) {
            return collect();
        }

        return $this->parentsQuery($broadcast)->get()->merge(
            $this->staffQuery($broadcast)->get()
        );
    }

    public function createInAppNotification(Model $owner, PushBroadcast $broadcast): UserNotification
    {
        return UserNotification::create([
            'notifiable_type' => $owner::class,
            'notifiable_id' => $owner->getKey(),
            'push_broadcast_id' => $broadcast->id,
            'title' => $broadcast->title,
            'body' => $broadcast->body,
            'data' => $broadcast->notificationData(),
        ]);
    }
}
