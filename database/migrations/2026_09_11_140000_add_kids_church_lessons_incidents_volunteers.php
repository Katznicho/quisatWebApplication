<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kids_lessons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('bible_lesson'); // bible_lesson, home_resource, pastor_devotional
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('object_lesson')->nullable();
            $table->text('craft_supplies')->nullable();
            $table->text('teaching_script')->nullable();
            $table->string('memory_verse')->nullable();
            $table->string('scripture_ref')->nullable();
            $table->text('family_challenge')->nullable();
            $table->string('video_url')->nullable();
            $table->date('lesson_date')->nullable();
            $table->string('status')->default('published'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'type', 'status']);
        });

        Schema::create('kids_incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('other'); // physical, behavioral, health, other
            $table->string('title');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->timestamp('notified_parent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'student_id']);
        });

        Schema::create('kids_volunteers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->string('background_check_status')->default('pending'); // pending, cleared, expired
            $table->decimal('hours_served', 8, 1)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kids_volunteers');
        Schema::dropIfExists('kids_incidents');
        Schema::dropIfExists('kids_lessons');
    }
};
