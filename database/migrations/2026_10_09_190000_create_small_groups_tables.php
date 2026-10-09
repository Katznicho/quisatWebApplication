<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('small_group_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('module_label')->default('Small Groups');
            $table->string('singular_label')->default('Small group');
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::create('small_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location');
            $table->string('host_name');
            $table->string('host_phone');
            $table->string('leader_name');
            $table->string('leader_phone');
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('capacity');
            $table->json('custom_values')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'location']);
            $table->index(['business_id', 'name']);
        });

        Schema::create('small_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_id')->constrained('small_groups')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolled_by_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['small_group_id', 'student_id']);
        });

        Schema::create('small_group_meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('small_group_id')->constrained('small_groups')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('meeting_date');
            $table->string('location');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'meeting_date']);
            $table->index(['small_group_id', 'meeting_date']);
        });

        Schema::create('small_group_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_meeting_id')->constrained('small_group_meetings')->cascadeOnDelete();
            $table->foreignId('small_group_id')->constrained('small_groups')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('present');
            $table->string('verification_status')->default('pending');
            $table->foreignId('submitted_by_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('last_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_changed_by_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['small_group_meeting_id', 'student_id'], 'small_group_attendance_unique');
        });

        Schema::create('small_group_attendance_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_attendance_id')->constrained('small_group_attendances')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->string('action');
            $table->string('summary');
            $table->timestamps();
        });

        if (Schema::hasTable('features') && Schema::hasTable('currencies')) {
            $exists = DB::table('features')->where('name', 'Small Groups')->exists();
            $currencyId = DB::table('currencies')->value('id');
            if (! $exists && $currencyId) {
                $row = [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Small Groups',
                    'description' => 'Local church groups. Parents enrol their children, and leaders record weekly meetings and attendance.',
                    'price' => '0',
                    'currency_id' => $currencyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('features', 'group')) {
                    $row['group'] = 'church';
                }
                DB::table('features')->insert($row);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('small_group_attendance_changes');
        Schema::dropIfExists('small_group_attendances');
        Schema::dropIfExists('small_group_meetings');
        Schema::dropIfExists('small_group_members');
        Schema::dropIfExists('small_groups');
        Schema::dropIfExists('small_group_settings');
    }
};
