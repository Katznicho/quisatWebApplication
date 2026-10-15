<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->foreignId('registered_by_parent_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('relationship');
            $table->text('care_notes')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('church_visitor_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('service_date');
            $table->string('service_name')->nullable();
            $table->string('status', 20)->default('awaiting');
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registered_by_parent_id')->constrained('parent_guardians')->cascadeOnDelete();
            $table->string('pickup_code', 8);
            $table->timestamp('pickup_used_at')->nullable();
            $table->string('active_key')->nullable()->unique();
            $table->string('client_token', 64)->nullable()->unique();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_parent_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('church_visitor_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained('church_visitor_registrations')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->date('service_date');
            $table->timestamp('checked_in_at');
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('collector_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_visitor_attendances');
        Schema::dropIfExists('church_visitor_registrations');
        Schema::dropIfExists('church_visitors');
    }
};
