<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canteens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->decimal('float_balance', 15, 2)->default(0);
            $table->unsignedInteger('low_float_threshold')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });

        Schema::create('canteen_attendants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canteen_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('pin')->nullable();
            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('failed_pin_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('status', 20)->default('pending_setup');
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pocket_pay_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('card_number', 32)->unique();
            $table->string('status', 20)->default('inactive');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->string('pin')->nullable();
            $table->unsignedTinyInteger('failed_pin_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->nullableMorphs('activated_by');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('canteen_sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('receipt_number', 32)->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canteen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canteen_attendant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pocket_pay_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('reversal_status', 20)->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamp('reversal_requested_at')->nullable();
            $table->foreignId('reversal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversal_reviewed_at')->nullable();
            $table->string('reversal_review_note')->nullable();
            $table->timestamps();

            $table->index(['canteen_id', 'created_at']);
            $table->index(['pocket_pay_card_id', 'created_at']);
            $table->index(['business_id', 'reversal_status']);
        });

        Schema::create('pocket_pay_topups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->morphs('topupable');
            $table->decimal('amount', 15, 2);
            $table->string('phone_number', 20);
            $table->string('status', 20)->default('pending');
            $table->nullableMorphs('initiated_by');
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('pocket_pay_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pocket_pay_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('canteen_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['pocket_pay_card_id', 'created_at']);
        });

        Schema::create('canteen_float_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('canteen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['canteen_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_float_transactions');
        Schema::dropIfExists('pocket_pay_card_transactions');
        Schema::dropIfExists('pocket_pay_topups');
        Schema::dropIfExists('canteen_sales');
        Schema::dropIfExists('pocket_pay_cards');
        Schema::dropIfExists('canteen_attendants');
        Schema::dropIfExists('canteens');
    }
};
