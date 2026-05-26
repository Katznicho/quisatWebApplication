<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_appointment_types')) {
            return;
        }

        Schema::create('clinic_appointment_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('applies_to', 30)->default('both');
            $table->string('status', 30)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'applies_to', 'status'], 'clinic_appt_types_business_apply_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_appointment_types');
    }
};
