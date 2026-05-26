<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_patient_growth_records')) {
            return;
        }

        Schema::create('clinic_patient_growth_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('recorded_on');
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('head_circumference_cm', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_patient_id', 'recorded_on'], 'clinic_patient_growth_patient_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patient_growth_records');
    }
};
