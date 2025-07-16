<?php

namespace App\Traits;

trait MemberTrait
{

    /**
     * Get the currently logged in motor user by sanctum.
     *
     * @return \App\Models\Member
     */
    public function getCurrentLoggedMemberBySanctum()
    {
        return auth('member')->user();
    }
}
