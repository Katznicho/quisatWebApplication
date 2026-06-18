<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->morphs('payable');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('UGX');
            $table->string('method');
            $table->string('phone_number')->nullable();
            $table->string('country', 2)->default('UG');
            $table->string('status')->default('pending');
            $table->string('marz_transaction_uuid')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->text('redirect_url')->nullable();
            $table->string('description')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('marz_transaction_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_collections');
    }
};
