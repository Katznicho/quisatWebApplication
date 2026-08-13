<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            if (! Schema::hasColumn('fees', 'invoice_document_id')) {
                $table->foreignId('invoice_document_id')
                    ->nullable()
                    ->after('receipt_number')
                    ->constrained('student_documents')
                    ->nullOnDelete();
            }
        });

        DB::statement("ALTER TABLE fees MODIFY COLUMN payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'check', 'card') NULL");

        Schema::table('student_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            if (Schema::hasColumn('fees', 'invoice_document_id')) {
                $table->dropConstrainedForeignId('invoice_document_id');
            }
        });

        DB::statement("ALTER TABLE fees MODIFY COLUMN payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'check') NULL");
    }
};
