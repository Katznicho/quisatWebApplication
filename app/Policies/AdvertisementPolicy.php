<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvertisementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any advertisements.
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view advertisements
    }

    /**
     * Determine whether the user can view the advertisement.
     */
    public function view(User $user, Advertisement $advertisement)
    {
        return $user->business_id === $advertisement->business_id;
    }

    /**
     * Determine whether the user can create advertisements.
     */
    public function create(User $user)
    {
        return true; // All authenticated users can create advertisements
    }

    /**
     * Determine whether the user can update the advertisement.
     */
    public function update(User $user, Advertisement $advertisement)
    {
        return $user->business_id === $advertisement->business_id;
    }

    /**
     * Determine whether the user can delete the advertisement.
     */
    public function delete(User $user, Advertisement $advertisement)
    {
        return $user->business_id === $advertisement->business_id;
    }

    /**
     * Determine whether the user can restore the advertisement.
     */
    public function restore(User $user, Advertisement $advertisement)
    {
        return $user->business_id === $advertisement->business_id;
    }

    /**
     * Determine whether the user can permanently delete the advertisement.
     */
    public function forceDelete(User $user, Advertisement $advertisement)
    {
        return $user->business_id === $advertisement->business_id;
    }
}