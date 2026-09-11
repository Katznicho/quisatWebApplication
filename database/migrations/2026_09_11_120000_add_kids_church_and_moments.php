<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'allergies')) {
                $table->text('allergies')->nullable()->after('photo');
            }
            if (! Schema::hasColumn('students', 'medical_notes')) {
                $table->text('medical_notes')->nullable()->after('allergies');
            }
            if (! Schema::hasColumn('students', 'dietary_restrictions')) {
                $table->text('dietary_restrictions')->nullable()->after('medical_notes');
            }
            if (! Schema::hasColumn('students', 'emergency_contacts')) {
                $table->json('emergency_contacts')->nullable()->after('dietary_restrictions');
            }
        });

        Schema::create('pickup_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->date('code_date');
            $table->string('code', 8);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'code_date']);
            $table->index(['business_id', 'code_date']);
        });

        Schema::create('quisat_albums', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('class_daily'); // class_daily, event, official_class_photo
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('status')->default('published'); // draft, published
            $table->boolean('is_hd_paid')->default(false);
            $table->decimal('hd_price', 12, 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quisat_album_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('album_id')->constrained('quisat_albums')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('media_type')->default('photo'); // photo, video
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quisat_album_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('quisat_album_media')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['media_id', 'student_id']);
        });

        Schema::create('quisat_album_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('quisat_albums')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('quisat_album_media')->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['album_id', 'media_id', 'parent_guardian_id'], 'quisat_album_likes_unique');
        });

        Schema::create('quisat_album_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('quisat_albums')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('quisat_album_media')->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('visible_to_staff_only')->default(true);
            $table->timestamps();
        });

        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('received'); // received, being_prayed_for, answered
            $table->text('praise_report')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('memory_wall_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // memory_verse, prayer_focus
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('scripture_ref')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_wall_items');
        Schema::dropIfExists('prayer_requests');
        Schema::dropIfExists('quisat_album_comments');
        Schema::dropIfExists('quisat_album_likes');
        Schema::dropIfExists('quisat_album_tags');
        Schema::dropIfExists('quisat_album_media');
        Schema::dropIfExists('quisat_albums');
        Schema::dropIfExists('pickup_codes');

        Schema::table('students', function (Blueprint $table) {
            foreach (['emergency_contacts', 'dietary_restrictions', 'medical_notes', 'allergies'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
