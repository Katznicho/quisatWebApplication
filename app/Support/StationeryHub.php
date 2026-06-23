<?php

namespace App\Support;

class StationeryHub
{
    public const HUB = 'stationery_hub';

    public const KIDZ_MART = 'kidz_mart';

    public static function featureName(): string
    {
        return (string) config('stationery_hub.feature_name', 'StationeryHub');
    }

    public static function categories(): array
    {
        return config('stationery_hub.categories', []);
    }

    public static function qualityOptions(): array
    {
        return config('stationery_hub.quality_grades', []);
    }

    public static function fulfillmentStatuses(): array
    {
        return config('stationery_hub.fulfillment_statuses', []);
    }
}
