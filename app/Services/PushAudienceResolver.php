<?php

namespace App\Services;

use App\Models\Business;
use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\PushBroadcast;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Concerns\ResolvesPushDeviceTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PushAudienceResolver
{
    use ResolvesPushDeviceTokens;

    /**
     * @return Collection<int, array{owner: Model, tokens: Collection<int, DeviceToken>}>
     */
    public function resolve(PushBroadcast $broadcast): Collection
    {
        $recipients = collect();

        foreach ($this->resolveOwners($broadcast) as $owner) {
            $tokens = $this->resolveDeviceTokens(collect([$owner]));

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
        $scopeToBusiness = $this->shouldScopeToBusiness($broadcast);

        return match ($broadcast->audience) {
            PushBroadcast::AUDIENCE_PARENTS => $this->parentsQuery($scopeToBusiness ? $broadcast->business_id : null)->get(),
            PushBroadcast::AUDIENCE_STAFF => $this->staffQuery($scopeToBusiness ? $broadcast->business_id : null)->get(),
            PushBroadcast::AUDIENCE_BUSINESS => $this->businessAudience($broadcast),
            default => $this->parentsQuery($scopeToBusiness ? $broadcast->business_id : null)
                ->get()
                ->merge($this->staffQuery($scopeToBusiness ? $broadcast->business_id : null)->get())
                ->unique(fn (Model $owner) => $owner::class.'#'.$owner->getKey())
                ->values(),
        };
    }

    protected function shouldScopeToBusiness(PushBroadcast $broadcast): bool
    {
        if ($broadcast->audience === PushBroadcast::AUDIENCE_BUSINESS) {
            return (bool) $broadcast->business_id;
        }

        if (! $broadcast->business_id) {
            return false;
        }

        $business = $broadcast->relationLoaded('business')
            ? $broadcast->business
            : Business::query()->with('businessCategory')->find($broadcast->business_id);

        return $business instanceof Business && $business->isSchool();
    }

    protected function parentsQuery(?int $businessId)
    {
        $query = ParentGuardian::query()->where('status', 'active');

        if ($businessId) {
            $query->where(function ($inner) use ($businessId) {
                $inner->where('business_id', $businessId)
                    ->orWhereHas('memberships', function ($membershipQuery) use ($businessId) {
                        $membershipQuery
                            ->where('business_id', $businessId)
                            ->where('status', 'active');
                    });
            });
        }

        return $query;
    }

    protected function staffQuery(?int $businessId)
    {
        $query = User::query()->where('status', 'active');

        if ($businessId) {
            $query->where('business_id', $businessId);
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

        return $this->parentsQuery((int) $broadcast->business_id)
            ->get()
            ->merge($this->staffQuery((int) $broadcast->business_id)->get())
            ->unique(fn (Model $owner) => $owner::class.'#'.$owner->getKey())
            ->values();
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
