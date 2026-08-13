<?php

namespace App\Services\Concerns;

use App\Models\DeviceToken;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait ResolvesPushDeviceTokens
{
    /**
     * @param  Collection<int, Model>  $recipients
     * @return Collection<int, DeviceToken>
     */
    protected function resolveDeviceTokens(Collection $recipients): Collection
    {
        $owners = $this->expandLinkedOwners($recipients);

        if ($owners->isEmpty()) {
            return collect();
        }

        return DeviceToken::query()
            ->where('is_active', true)
            ->where(function ($query) use ($owners) {
                foreach ($owners as $recipient) {
                    $query->orWhere(function ($ownerQuery) use ($recipient) {
                        $ownerQuery
                            ->where('tokenable_type', $recipient::class)
                            ->where('tokenable_id', $recipient->getKey());
                    });
                }
            })
            ->get();
    }

    /**
     * Parents authenticate as ParentGuardian but may also have a shadow User.
     *
     * @param  Collection<int, Model>  $recipients
     * @return Collection<int, Model>
     */
    protected function expandLinkedOwners(Collection $recipients): Collection
    {
        $owners = $recipients->values();

        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient->email ?? '')));
            if ($email === '') {
                continue;
            }

            if ($recipient instanceof User) {
                $parent = ParentGuardian::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->first();
                if ($parent) {
                    $owners->push($parent);
                }
            }

            if ($recipient instanceof ParentGuardian) {
                $user = User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->first();
                if ($user) {
                    $owners->push($user);
                }
            }
        }

        return $owners
            ->unique(fn (Model $owner) => $owner::class.'#'.$owner->getKey())
            ->values();
    }
}
