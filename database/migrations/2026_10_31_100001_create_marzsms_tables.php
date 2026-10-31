<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marzsms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->unsignedInteger('low_balance_threshold')->default(5000);
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 30);
            $table->text('message');
            $table->string('purpose', 64)->default('general');
            $table->nullableMorphs('related');
            $table->string('status', 20)->default('pending');
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('error')->nullable();
            $table->json('response_payload')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('purpose');
            $table->index(['business_id', 'created_at']);
        });

        Schema::create('marzsms_topups', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->decimal('amount', 12, 2);
            $table->decimal('charge', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('phone_number', 20);
            $table->string('status', 20)->default('pending');
            $table->string('message')->nullable();
            $table->json('response_payload')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marzsms_topups');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('marzsms_settings');
    }
};
