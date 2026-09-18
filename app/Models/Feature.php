<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;


class Feature extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const GROUP_SCHOOL = 'school';
    public const GROUP_CHURCH = 'church';
    public const GROUP_MARKETPLACE = 'marketplace';

    public const GROUPS = [
        self::GROUP_SCHOOL => 'School',
        self::GROUP_CHURCH => 'Church',
        self::GROUP_MARKETPLACE => 'Marketplace',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'group',
        'price',
        'currency_id',
    ];

    protected static function booted()
    {
        static::creating(function (Feature $feature) {
            $feature->uuid = (string) Str::uuid();
            if (empty($feature->group)) {
                $feature->group = static::groupForName((string) $feature->name);
            }
        });
    }

    public function getRouteKeyName()  {
        return 'uuid';
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function groupLabel(): string
    {
        return self::GROUPS[$this->group] ?? self::GROUPS[self::GROUP_MARKETPLACE];
    }

    /**
     * Known feature names mapped to school, church, or marketplace.
     */
    public static function groupForName(string $name): string
    {
        $normalized = strtolower(trim($name));

        $school = [
            'subject management',
            'class room management',
            'timetable management',
            'exam management',
            'grade management',
            'student management',
            'parent guardian management',
            'attendance management',
            'fee management',
            'payment processing',
            'multi-branch management',
            'role-based access control',
            'staff management',
            'report generation',
            'communication system',
            'document management',
            'term management',
            'calendar & events management',
        ];

        $church = [
            'kids church',
            'quisat moments',
        ];

        if (in_array($normalized, $school, true)) {
            return self::GROUP_SCHOOL;
        }

        if (in_array($normalized, $church, true)) {
            return self::GROUP_CHURCH;
        }

        return self::GROUP_MARKETPLACE;
    }

    public static function checkboxOptions(): array
    {
        $groupOrder = [
            self::GROUP_SCHOOL => 0,
            self::GROUP_CHURCH => 1,
            self::GROUP_MARKETPLACE => 2,
        ];

        return static::query()
            ->orderBy('name')
            ->get()
            ->sortBy(function (self $feature) use ($groupOrder) {
                return sprintf('%d-%s', $groupOrder[$feature->group] ?? 9, strtolower($feature->name));
            })
            ->mapWithKeys(function (self $feature) {
                return [$feature->id => $feature->name.' ('.$feature->groupLabel().')'];
            })
            ->all();
    }

    public static function optionsInGroup(string $group): array
    {
        return static::query()
            ->where('group', $group)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Keep already-enabled features and add the new category defaults.
     * This lets a school also enable church features without losing school ones.
     */
    public static function mergeWithCategoryDefaults(array $currentIds, array $categoryIds): array
    {
        $merged = array_merge(
            array_map('intval', $currentIds),
            array_map('intval', $categoryIds)
        );

        return array_values(array_unique(array_filter($merged)));
    }
}
