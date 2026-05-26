<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_patient_visits')) {
            return;
        }

        Schema::create('clinic_patient_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('visited_at');
            $table->string('doctor_name')->nullable();
            $table->string('visit_type', 50)->default('consultation');
            $table->string('status', 30)->default('completed');
            $table->text('chief_complaint')->nullable();
            $table->text('consultation_notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('prescriptions')->nullable();
            $table->text('lab_results')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_patient_id', 'visited_at'], 'clinic_patient_visits_patient_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patient_visits');
    }
};
