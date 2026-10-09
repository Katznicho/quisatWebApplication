<?php

namespace App\Support;

use App\Models\Business;
use App\Models\PackagePlan;
use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrganisationPackage
{
    public const GOLD = 'gold';

    public const SILVER = 'silver';

    public const FREE = 'free';

    /**
     * Community services controlled by the organisation package.
     * Clinics stays available on every package.
     *
     * @var array<string, array{label: string, chip: string, always: bool}>
     */
    public const SERVICES = [
        'clinics' => ['label' => 'Clinics', 'chip' => 'clinics', 'always' => true],
        'kidz_mart' => ['label' => "Kid's Mart & Stationery", 'chip' => 'kidz-mart', 'always' => false],
        'kids_events' => ['label' => 'Kids Events & Places', 'chip' => 'kids-events', 'always' => false],
        'parent_corner' => ['label' => 'Parent Corner', 'chip' => 'parent-corner', 'always' => false],
        'christian_kids_hub' => ['label' => 'Christian Kids Hub', 'chip' => 'programs', 'always' => false],
        'adverts' => ['label' => 'Quisat Adverts', 'chip' => 'adverts', 'always' => false],
        'support_child' => ['label' => 'Support a Child', 'chip' => 'support', 'always' => false],
    ];

    public static function keys(): array
    {
        return [self::GOLD, self::SILVER, self::FREE];
    }

    public static function toggleableOptions(): array
    {
        $options = [];
        foreach (self::SERVICES as $key => $service) {
            if (! $service['always']) {
                $options[$key] = $service['label'];
            }
        }

        return $options;
    }

    public static function planOptions(): array
    {
        $options = [];
        foreach (self::keys() as $key) {
            $plan = self::findPlan($key);
            $name = $plan?->name ?: ucfirst($key);
            $price = $plan?->priceLabel();
            $options[$key] = $price ? $name.' · '.$price : $name;
        }

        return $options;
    }

    private static function findPlan(string $key): ?PackagePlan
    {
        try {
            if (! Schema::hasTable('package_plans')) {
                return null;
            }

            return PackagePlan::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    public static function enabledServices(Business $business): array
    {
        $package = $business->package ?: self::FREE;
        if (! in_array($package, self::keys(), true)) {
            $package = self::FREE;
        }

        if ($package === self::FREE) {
            return array_keys(self::SERVICES);
        }

        $enabled = ['clinics'];
        if ($package === self::SILVER) {
            $chosen = is_array($business->package_services) ? $business->package_services : [];
            foreach ($chosen as $key) {
                if (isset(self::SERVICES[$key]) && ! self::SERVICES[$key]['always']) {
                    $enabled[] = $key;
                }
            }
        }

        return array_values(array_unique($enabled));
    }

    public static function allows(Business $business, string $service): bool
    {
        return in_array($service, self::enabledServices($business), true);
    }

    public static function payload(Business $business): array
    {
        $key = in_array($business->package, self::keys(), true) ? $business->package : self::FREE;
        $plan = self::findPlan($key);

        return [
            'package' => $key,
            'package_name' => $plan?->name ?: ucfirst($key),
            'package_price' => $plan?->price !== null ? (string) $plan->price : null,
            'package_currency' => $plan?->currency_code,
            'community_services' => self::enabledServices($business),
        ];
    }

    public static function businessFromRequest(Request $request): ?Business
    {
        $user = $request->user() ?: auth('sanctum')->user();
        if (! $user) {
            return null;
        }

        if ($user instanceof ParentGuardian) {
            $requested = (int) ($request->header('X-Business-Id') ?: 0);

            return $user->resolveScopedBusiness($requested ?: null);
        }

        if ($user instanceof User) {
            return $user->business;
        }

        return null;
    }

    public static function serviceForPath(string $path): ?string
    {
        $path = trim($path, '/');
        $prefixes = [
            'api/v1/products' => 'kidz_mart',
            'api/v1/orders' => 'kidz_mart',
            'api/v1/kids-events' => 'kids_events',
            'api/v1/kids-fun-venues' => 'kids_events',
            'api/v1/parent-corners' => 'parent_corner',
            'api/v1/my-parent-corner-registrations' => 'parent_corner',
            'api/v1/programmes' => 'christian_kids_hub',
            'api/v1/my-programmes' => 'christian_kids_hub',
            'api/v1/advertisements' => 'adverts',
            'api/v1/support-children' => 'support_child',
            'api/v1/clinics' => 'clinics',
        ];

        foreach ($prefixes as $prefix => $service) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $service;
            }
        }

        return null;
    }
}
