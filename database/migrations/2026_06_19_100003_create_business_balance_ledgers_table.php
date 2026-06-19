<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_balance_ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit', 'withdrawal_fee']);
            $table->decimal('amount', 15, 2);
            $table->decimal('available_balance_after', 15, 2);
            $table->decimal('total_balance_after', 15, 2);
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_balance_ledgers');
    }
};
