<?php

namespace App\Http\Controllers\Concerns;

use App\Support\CountryFilter;
use App\Support\CountryScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ResolvesCountryFilter
{
    protected function countryFilter(?Request $request = null): ?CountryFilter
    {
        return CountryScope::resolve($request);
    }

    protected function scopeQueryByCountry(
        Builder $query,
        ?Request $request = null,
        string $relation = 'business'
    ): Builder {
        return CountryScope::applyViaBusinessRelation(
            $query,
            $this->countryFilter($request),
            $relation
        );
    }
}
