<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_patients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_family_id')->constrained('clinic_families')->cascadeOnDelete();
            $table->foreignId('parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('patient_number', 30);
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->json('allergies')->nullable();
            $table->json('emergency_contacts')->nullable();
            $table->json('insurance_info')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'patient_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patients');
    }
};
