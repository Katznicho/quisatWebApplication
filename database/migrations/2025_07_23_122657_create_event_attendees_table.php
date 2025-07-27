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
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('program_event_id')->references('id')->on('program_events')->onDelete('cascade');
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('child_name')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('child_age')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'airtel_money', 'mtn_mobile_money', 'other'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('amount_paid')->default(0);
            $table->string('amount_due')->default(0);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};
