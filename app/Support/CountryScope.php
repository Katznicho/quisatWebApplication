<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Country;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CountryScope
{
    /**
     * Resolve which country marketplace/browse results should be scoped to.
     *
     * Priority: explicit query params → authenticated staff business → authenticated parent
     * → default country for guests. Platform super-admins (business_id = 1) see all unless
     * they pass an explicit country filter.
     */
    public static function resolve(?Request $request = null): ?CountryFilter
    {
        $request ??= request();

        if ($request->filled('country_id')) {
            $country = Country::find((int) $request->query('country_id'));

            return new CountryFilter(
                countryId: (int) $request->query('country_id'),
                countryName: $country?->name,
            );
        }

        if ($request->filled('country')) {
            $countryName = trim((string) $request->query('country'));
            $country = Country::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($countryName)])
                ->first();

            return new CountryFilter(
                countryId: $country?->id,
                countryName: $countryName,
            );
        }

        $actor = self::resolveAuthenticatedActor();

        if ($actor instanceof User) {
            if ((int) ($actor->business_id ?? 0) === 1) {
                return null;
            }

            $actor->loadMissing('business');
            $filter = self::fromBusiness($actor->business);

            return $filter?->applies() ? $filter : self::defaultFilter();
        }

        if ($actor instanceof ParentGuardian) {
            return self::fromParent($actor);
        }

        return self::defaultFilter();
    }

    public static function applyToBusinessQuery(Builder $query, ?CountryFilter $filter): Builder
    {
        if (! $filter?->applies()) {
            return $query;
        }

        return $query->where(function (Builder $countryQuery) use ($filter) {
            if ($filter->countryId !== null && filled($filter->countryName)) {
                $countryQuery->where(function (Builder $inner) use ($filter) {
                    $inner->where('country_id', $filter->countryId)
                        ->orWhere(function (Builder $legacy) use ($filter) {
                            $legacy->whereNull('country_id')
                                ->whereRaw('LOWER(country) = ?', [strtolower((string) $filter->countryName)]);
                        });
                });
            } elseif ($filter->countryId !== null) {
                $countryQuery->where('country_id', $filter->countryId);
            } elseif (filled($filter->countryName)) {
                $countryQuery->whereRaw('LOWER(country) = ?', [strtolower((string) $filter->countryName)]);
            }
        });
    }

    public static function applyViaBusinessRelation(
        Builder $query,
        ?CountryFilter $filter,
        string $relation = 'business'
    ): Builder {
        if (! $filter?->applies()) {
            return $query;
        }

        return $query->whereHas($relation, function (Builder $businessQuery) use ($filter) {
            self::applyToBusinessQuery($businessQuery, $filter);
        });
    }

    public static function businessMatches(Business $business, ?CountryFilter $filter): bool
    {
        if (! $filter?->applies()) {
            return true;
        }

        if ($filter->countryId !== null && (int) $business->country_id === $filter->countryId) {
            return true;
        }

        if (
            filled($filter->countryName)
            && $business->country_id === null
            && strcasecmp((string) $business->country, (string) $filter->countryName) === 0
        ) {
            return true;
        }

        return false;
    }

    public static function fromBusiness(?Business $business): ?CountryFilter
    {
        if (! $business) {
            return null;
        }

        if ($business->country_id || filled($business->country)) {
            return new CountryFilter(
                countryId: $business->country_id ? (int) $business->country_id : null,
                countryName: $business->country ?: null,
            );
        }

        return null;
    }

    public static function fromParent(ParentGuardian $parent): ?CountryFilter
    {
        $parent->loadMissing(['business', 'memberships.business']);

        if ($parent->business) {
            $filter = self::fromBusiness($parent->business);
            if ($filter?->applies()) {
                return $filter;
            }
        }

        foreach ($parent->memberships as $membership) {
            $filter = self::fromBusiness($membership->business);
            if ($filter?->applies()) {
                return $filter;
            }
        }

        if (filled($parent->country)) {
            $country = Country::query()
                ->whereRaw('LOWER(name) = ?', [strtolower((string) $parent->country)])
                ->first();

            return new CountryFilter(
                countryId: $country?->id,
                countryName: (string) $parent->country,
            );
        }

        return self::defaultFilter();
    }

    protected static function resolveAuthenticatedActor(): User|ParentGuardian|null
    {
        $user = Auth::user();
        if ($user instanceof User || $user instanceof ParentGuardian) {
            return $user;
        }

        $sanctumUser = auth('sanctum')->user();
        if ($sanctumUser instanceof User || $sanctumUser instanceof ParentGuardian) {
            return $sanctumUser;
        }

        return null;
    }

    protected static function defaultFilter(): ?CountryFilter
    {
        $defaultCountry = Country::query()->where('is_default', true)->first()
            ?? Country::query()->orderBy('id')->first();

        if (! $defaultCountry) {
            return null;
        }

        return new CountryFilter(
            countryId: (int) $defaultCountry->id,
            countryName: $defaultCountry->name,
        );
    }
}
