<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_patient_vaccinations')) {
            return;
        }

        Schema::create('clinic_patient_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vaccine_name');
            $table->string('dose_label', 80)->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('administered_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_patient_id', 'status'], 'clinic_patient_vaccinations_patient_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patient_vaccinations');
    }
};
