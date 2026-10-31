<?php

namespace App\Support;

use App\Models\User;
use App\Services\PocketPayService;
use Illuminate\Support\Facades\Auth;

/**
 * Who may manage Pocket Pay on the web dashboard: super admins (all schools) and the
 * Admin role of a school that has the feature enabled (their own school only).
 * Card numbers, canteen float top-ups and sale reversals are super admin only.
 */
class PocketPayAccess
{
    public static function canManage(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBusinessAdmin() && app(PocketPayService::class)->isEnabledFor($user->business);
    }

    public static function canAssignCards(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function canManageFloat(?User $user = null): bool
    {
        return self::canAssignCards($user);
    }

    public static function canReviewReversals(?User $user = null): bool
    {
        return self::canAssignCards($user);
    }

    public static function authorize(): void
    {
        abort_unless(self::canManage(), 403, 'Quisat Pocket Pay is not available for your account.');
    }
}
