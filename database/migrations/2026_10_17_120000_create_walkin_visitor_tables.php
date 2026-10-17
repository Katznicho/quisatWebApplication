<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walkin_families', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('guardian_name');
            $table->string('phone');
            $table->string('phone_digits', 32)->index();
            $table->string('relationship');
            $table->string('collector_name');
            $table->boolean('participation_consent')->default(false);
            $table->boolean('follow_up_consent')->default(false);
            $table->string('consent_version', 32)->default('2026-10-12');
            $table->timestamp('consented_at')->nullable();
            $table->foreignId('consented_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('walkin_children', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('walkin_family_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->text('care_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('walkin_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('walkin_family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('service_date');
            $table->string('client_token', 64)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('walkin_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('walkin_checkin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('walkin_child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('walkin_family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('service_date');
            $table->string('service_name')->nullable();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('checked_in');
            $table->string('pickup_code', 8);
            $table->timestamp('checked_in_at');
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('collector_name')->nullable();
            $table->string('exception_reason')->nullable();
            $table->string('active_key')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walkin_visits');
        Schema::dropIfExists('walkin_checkins');
        Schema::dropIfExists('walkin_children');
        Schema::dropIfExists('walkin_families');
    }
};
