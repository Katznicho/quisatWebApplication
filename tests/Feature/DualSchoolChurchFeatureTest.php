<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\ParentGuardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DualSchoolChurchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_features_are_grouped_as_school_church_or_marketplace(): void
    {
        $this->assertSame(Feature::GROUP_SCHOOL, Feature::groupForName('Student Management'));
        $this->assertSame(Feature::GROUP_SCHOOL, Feature::groupForName('Term Management'));
        $this->assertSame(Feature::GROUP_CHURCH, Feature::groupForName('Kids Church'));
        $this->assertSame(Feature::GROUP_CHURCH, Feature::groupForName('Quisat Moments'));
        $this->assertSame(Feature::GROUP_MARKETPLACE, Feature::groupForName('KidsMart'));
        $this->assertSame(Feature::GROUP_MARKETPLACE, Feature::groupForName('Kids Clinics'));
    }

    public function test_new_features_inherit_their_group_from_the_feature_name(): void
    {
        $this->makeCurrency();

        $school = Feature::create([
            'name' => 'Student Management',
            'description' => 'Students',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);
        $church = Feature::create([
            'name' => 'Kids Church',
            'description' => 'Church',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);
        $marketplace = Feature::create([
            'name' => 'KidsMart',
            'description' => 'Shop',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);

        $this->assertSame(Feature::GROUP_SCHOOL, $school->group);
        $this->assertSame(Feature::GROUP_CHURCH, $church->group);
        $this->assertSame(Feature::GROUP_MARKETPLACE, $marketplace->group);
    }

    public function test_changing_category_keeps_already_enabled_features_from_other_groups(): void
    {
        $schoolIds = [10, 11];
        $churchIds = [20];

        $this->assertSame(
            [10, 11, 20],
            Feature::mergeWithCategoryDefaults($schoolIds, $churchIds)
        );
    }

    public function test_church_category_business_with_school_features_is_both_school_and_church(): void
    {
        [$schoolFeature, $churchFeature] = $this->makeSchoolAndChurchFeatures();

        $category = BusinessCategory::factory()->create([
            'name' => 'Church',
            'feature_ids' => [$churchFeature->id],
        ]);

        $business = Business::factory()->create([
            'type' => 'church',
            'business_category_id' => $category->id,
            'enabled_feature_ids' => [$churchFeature->id, $schoolFeature->id],
        ]);

        $this->assertTrue($business->isChurch());
        $this->assertTrue($business->isSchool());
        $this->assertFalse($business->usesKidsChurchHub());
        $this->assertContains(Feature::GROUP_SCHOOL, $business->enabledFeatureGroups());
        $this->assertContains(Feature::GROUP_CHURCH, $business->enabledFeatureGroups());
    }

    public function test_category_feature_sync_does_not_strip_school_features_from_a_church_tenant(): void
    {
        [$schoolFeature, $churchFeature] = $this->makeSchoolAndChurchFeatures();
        $extraChurchFeature = Feature::create([
            'name' => 'Quisat Moments',
            'description' => 'Photos',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);

        $category = BusinessCategory::factory()->create([
            'name' => 'Church',
            'feature_ids' => [$churchFeature->id, $extraChurchFeature->id],
        ]);

        $business = Business::factory()->create([
            'type' => 'church',
            'business_category_id' => $category->id,
            'enabled_feature_ids' => [$churchFeature->id, $extraChurchFeature->id, $schoolFeature->id],
        ]);

        $category->update([
            'feature_ids' => [$churchFeature->id],
        ]);

        $business->refresh();
        $enabled = array_map('intval', $business->enabled_feature_ids ?? []);

        $this->assertContains($churchFeature->id, $enabled);
        $this->assertContains($schoolFeature->id, $enabled);
        $this->assertNotContains($extraChurchFeature->id, $enabled);
        $this->assertTrue($business->isSchool());
        $this->assertTrue($business->isChurch());
    }

    public function test_parent_dashboard_keeps_school_flags_for_a_dual_tenant(): void
    {
        [$schoolFeature, $churchFeature] = $this->makeSchoolAndChurchFeatures();

        $business = Business::factory()->create([
            'type' => 'church',
            'name' => 'Grace School & Church',
            'enabled_feature_ids' => [$churchFeature->id, $schoolFeature->id],
        ]);

        $parent = ParentGuardian::factory()->linked($business->id)->create();

        Sanctum::actingAs($parent);

        $this->getJson('/api/v1/parent/dashboard', [
            'X-Business-Id' => (string) $business->id,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_school', true)
            ->assertJsonPath('data.is_church', true)
            ->assertJsonPath('data.business_id', $business->id);
    }

    /**
     * @return array{0: Feature, 1: Feature}
     */
    private function makeSchoolAndChurchFeatures(): array
    {
        $this->makeCurrency();

        $schoolFeature = Feature::create([
            'name' => 'Student Management',
            'description' => 'Students',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);
        $churchFeature = Feature::create([
            'name' => 'Kids Church',
            'description' => 'Church',
            'price' => '0',
            'currency_id' => Currency::query()->value('id'),
        ]);

        return [$schoolFeature, $churchFeature];
    }

    private function makeCurrency(): Currency
    {
        return Currency::query()->first() ?? Currency::create([
            'name' => 'Ugandan Shilling',
            'code' => 'UGX',
            'symbol' => 'UGX',
            'rate' => '1',
            'status' => 'active',
            'is_default' => true,
        ]);
    }
}
