<?php

namespace App\Support;

readonly class CountryFilter
{
    public function __construct(
        public ?int $countryId = null,
        public ?string $countryName = null,
    ) {}

    public function applies(): bool
    {
        return $this->countryId !== null || filled($this->countryName);
    }
}
