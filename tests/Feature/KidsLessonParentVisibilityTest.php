<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\KidsLesson;
use App\Models\ParentGuardian;
use App\Models\ParentGuardianBusiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KidsLessonParentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_sees_published_church_lessons_even_when_primary_business_is_a_school(): void
    {
        $school = Business::factory()->create(['type' => 'school', 'name' => 'Sunrise School']);
        $church = Business::factory()->create(['type' => 'church', 'name' => 'Grace Kids Church']);

        $parent = ParentGuardian::factory()->linked($school->id)->create();
        ParentGuardianBusiness::create([
            'parent_guardian_id' => $parent->id,
            'business_id' => $school->id,
            'status' => 'active',
            'joined_via' => 'staff_create',
            'joined_at' => now(),
        ]);
        ParentGuardianBusiness::create([
            'parent_guardian_id' => $parent->id,
            'business_id' => $church->id,
            'status' => 'active',
            'joined_via' => 'staff_create',
            'joined_at' => now(),
        ]);

        foreach (['bible_lesson' => 'Noah’s Ark', 'home_resource' => 'Family craft', 'pastor_devotional' => 'Sunday devotion'] as $type => $title) {
            KidsLesson::create([
                'business_id' => $church->id,
                'type' => $type,
                'title' => $title,
                'body' => $title.' body',
                'status' => 'published',
                'published_at' => now(),
                'lesson_date' => now()->toDateString(),
            ]);
        }

        Sanctum::actingAs($parent);

        $lessons = $this->getJson('/api/v1/kids-lessons')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.lessons');

        $titles = collect($lessons)->pluck('title');
        $this->assertTrue($titles->contains('Noah’s Ark'));
        $this->assertTrue($titles->contains('Family craft'));
        $this->assertTrue($titles->contains('Sunday devotion'));

        $dashboard = $this->getJson('/api/v1/parent/dashboard', [
            'X-Business-Id' => (string) $church->id,
        ])->assertOk()->json('data');

        $this->assertSame($church->id, $dashboard['business_id']);
        $this->assertTrue($dashboard['is_church']);
        $this->assertSame('Noah’s Ark', $dashboard['this_week_lesson']['title']);
        $this->assertSame('Family craft', $dashboard['home_resource']['title']);
        $this->assertSame('Sunday devotion', $dashboard['pastor_devotional']['title']);
    }
}
