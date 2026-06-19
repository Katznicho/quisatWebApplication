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

    public static function gradeOptions(): array
    {
        return config('stationery_hub.grade_levels', []);
    }

    public static function qualityOptions(): array
    {
        return config('stationery_hub.quality_grades', []);
    }

    public static function fulfillmentStatuses(): array
    {
        return config('stationery_hub.fulfillment_statuses', []);
    }

    public static function normalizeGrades(?array $grades): array
    {
        if (empty($grades)) {
            return [];
        }

        $valid = array_keys(self::gradeOptions());

        return array_values(array_unique(array_filter(array_map(
            fn ($g) => is_string($g) ? strtolower(trim($g)) : null,
            $grades
        ), fn ($g) => $g && in_array($g, $valid, true))));
    }

    public static function gradeLabel(string $key): string
    {
        return self::gradeOptions()[$key] ?? ucfirst($key);
    }
}
