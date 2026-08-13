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
            if (! Schema::hasColumn('fees', 'term_label')) {
                $table->string('term_label')->nullable()->after('term_id');
            }
            if (! Schema::hasColumn('fees', 'external_payment_system')) {
                $table->string('external_payment_system')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('fees', 'external_student_code')) {
                $table->string('external_student_code')->nullable()->after('external_payment_system');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fees MODIFY COLUMN payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'check', 'card', 'other') NULL");
        }

        if (! Schema::hasTable('fee_payments')) {
            Schema::create('fee_payments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('fee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_guardian_id')->nullable()->constrained('parent_guardians')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('method', 40);
                $table->string('status', 30)->default('completed');
                $table->string('receipt_number')->nullable();
                $table->string('proof_path')->nullable();
                $table->string('proof_url')->nullable();
                $table->string('proof_mime_type')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['fee_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');

        Schema::table('fees', function (Blueprint $table) {
            foreach (['term_label', 'external_payment_system', 'external_student_code'] as $column) {
                if (Schema::hasColumn('fees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
