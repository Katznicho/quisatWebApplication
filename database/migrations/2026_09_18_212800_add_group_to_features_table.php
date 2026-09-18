<?php

use App\Models\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('group')->default(Feature::GROUP_MARKETPLACE)->after('description');
        });

        foreach (DB::table('features')->select('id', 'name')->get() as $feature) {
            DB::table('features')->where('id', $feature->id)->update([
                'group' => Feature::groupForName((string) $feature->name),
            ]);
        }

        $this->restoreSchoolFeaturesOnDualBusinesses();
    }

    /**
     * Dual school+church tenants often lost school modules when church was added.
     * Put school-group features back without removing church or marketplace ones.
     */
    private function restoreSchoolFeaturesOnDualBusinesses(): void
    {
        $schoolFeatureIds = DB::table('features')
            ->where('group', Feature::GROUP_SCHOOL)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $churchFeatureIds = DB::table('features')
            ->where('group', Feature::GROUP_CHURCH)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($schoolFeatureIds === []) {
            return;
        }

        $categories = DB::table('business_categories')->get()->keyBy('id');

        foreach (DB::table('businesses')->select('id', 'type', 'business_category_id', 'enabled_feature_ids')->get() as $business) {
            $enabled = json_decode($business->enabled_feature_ids ?: '[]', true);
            $enabled = is_array($enabled) ? array_map('intval', $enabled) : [];
            $categoryName = strtolower((string) ($categories[$business->business_category_id]->name ?? ''));
            $type = strtolower((string) $business->type);

            $looksLikeSchool = str_contains($type, 'school') || str_contains($categoryName, 'school');
            $looksLikeChurch = str_contains($type, 'church')
                || str_contains($type, 'ministry')
                || str_contains($categoryName, 'church')
                || str_contains($categoryName, 'ministry')
                || array_intersect($enabled, $churchFeatureIds) !== [];

            if (! $looksLikeSchool || ! $looksLikeChurch) {
                continue;
            }

            if (array_intersect($enabled, $schoolFeatureIds) !== []) {
                continue;
            }

            $merged = array_values(array_unique(array_merge($enabled, $schoolFeatureIds)));
            if ($merged === array_values($enabled)) {
                continue;
            }

            DB::table('businesses')->where('id', $business->id)->update([
                'enabled_feature_ids' => json_encode($merged),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
