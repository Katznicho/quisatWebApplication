<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pocket_pay_withdrawals')) {
            return;
        }

        Schema::create('pocket_pay_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20);
            $table->string('phone_number', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('status', 20)->default('processing');
            $table->string('marz_transaction_uuid')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_pay_withdrawals');
    }
};
