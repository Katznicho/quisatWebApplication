<?php

namespace App\Support;

class ProductCategory
{
    public static function categories(): array
    {
        return config('product_categories.categories', []);
    }

    public static function options(): array
    {
        return array_combine(self::categories(), self::categories());
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($value === '') {
            return null;
        }

        $key = strtolower($value);

        $aliases = config('product_categories.aliases', []);
        if (isset($aliases[$key])) {
            return $aliases[$key];
        }

        foreach (self::categories() as $category) {
            if (strtolower($category) === $key) {
                return $category;
            }
        }

        return 'Other';
    }

    /**
     * Values that may exist in the database for a canonical category.
     */
    public static function matchingStoredValues(string $canonical): array
    {
        $canonical = self::normalize($canonical);
        if ($canonical === null) {
            return [];
        }

        $values = [$canonical];

        foreach (config('product_categories.aliases', []) as $alias => $mapped) {
            if ($mapped === $canonical) {
                $values[] = $alias;
                $values[] = ucfirst($alias);
                $values[] = ucwords($alias);
            }
        }

        return array_values(array_unique($values));
    }

    public static function categoriesInUse(): array
    {
        return self::categories();
    }
}
