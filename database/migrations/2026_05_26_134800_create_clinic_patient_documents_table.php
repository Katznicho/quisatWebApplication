<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clinic_patient_documents')) {
            return;
        }

        Schema::create('clinic_patient_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_patient_id')->constrained('clinic_patients')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50)->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['clinic_patient_id', 'type'], 'clinic_patient_docs_patient_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_patient_documents');
    }
};
