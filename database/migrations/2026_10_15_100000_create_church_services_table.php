<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('schedule_type', 20);
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->date('service_date')->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->string('timezone', 64)->default('Africa/Nairobi');
            $table->timestamps();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('church_service_id')->nullable()->after('class_room_id')->constrained('church_services')->nullOnDelete();
            $table->unsignedBigInteger('service_slot')->default(0)->after('church_service_id');
            $table->string('service_name')->nullable()->after('service_slot');
            $table->time('service_starts_at')->nullable()->after('service_name');
            $table->time('service_ends_at')->nullable()->after('service_starts_at');
            $table->dropUnique('unique_attendance');
            $table->unique(
                ['student_id', 'attendance_date', 'class_room_id', 'service_slot'],
                'unique_attendance_service'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('unique_attendance_service');
            $table->dropConstrainedForeignId('church_service_id');
            $table->dropColumn(['service_slot', 'service_name', 'service_starts_at', 'service_ends_at']);
            $table->unique(['student_id', 'attendance_date', 'class_room_id'], 'unique_attendance');
        });

        Schema::dropIfExists('church_services');
    }
};
