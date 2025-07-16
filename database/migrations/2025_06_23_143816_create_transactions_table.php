<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('cascade')->index();
            $table->foreignId('branch_id')->nullable()->index();
            $table->string('amount')->nullable();
            $table->string('reference')->index(); // used for lookups
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'processing'])->nullable()->index();
            $table->enum("type", ["credit", "debit"])->index();
            $table->enum("origin", ["api", "mobile", "web", 'payment_link'])->index();
            $table->string("phone_number")->nullable();
            $table->enum('provider', ['mtn', 'airtel'])->index();
            $table->string('service');
            $table->date('date')->default(now())->index();
            $table->string("currency")->default('UGX');
            $table->string("names")->nullable();
            $table->string("email")->nullable();
            $table->string("ip_address")->nullable();
            $table->string("user_agent")->nullable();
            $table->string('method')->default('card'); // card, mobile_money, bank_transfer, crypto
            $table->enum('transaction_for', ['main', 'charge'])->default('main')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
