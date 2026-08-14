<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            if (! Schema::hasColumn('fees', 'clinic_patient_id')) {
                $table->foreignId('clinic_patient_id')
                    ->nullable()
                    ->constrained('clinic_patients')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('fees', 'clinic_invoice_document_id')) {
                $table->foreignId('clinic_invoice_document_id')
                    ->nullable()
                    ->constrained('clinic_patient_documents')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('fees', 'student_id')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->foreignId('student_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            if (Schema::hasColumn('fees', 'clinic_invoice_document_id')) {
                $table->dropConstrainedForeignId('clinic_invoice_document_id');
            }
            if (Schema::hasColumn('fees', 'clinic_patient_id')) {
                $table->dropConstrainedForeignId('clinic_patient_id');
            }
        });
    }
};
