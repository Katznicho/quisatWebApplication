<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider as BaseEloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use RuntimeException;

class EloquentUserProvider extends BaseEloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * Non-bcrypt (or otherwise invalid) stored hashes must fail login
     * as invalid credentials instead of throwing a 500.
     *
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        try {
            return parent::validateCredentials($user, $credentials);
        } catch (RuntimeException) {
            return false;
        }
    }
}
