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
        Schema::create('kids_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kids_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // nullable for guest registrations
            
            // Child information
            $table->string('child_name');
            $table->integer('child_age');
            
            // Parent/Guardian information
            $table->string('parent_name');
            $table->string('parent_email');
            $table->string('parent_phone');
            $table->string('emergency_contact')->nullable();
            
            // Additional information
            $table->text('medical_conditions')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('notes')->nullable();
            
            // Payment information
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'airtel_money', 'mtn_mobile_money', 'other'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Registration status
            $table->enum('registration_status', ['pending', 'confirmed', 'cancelled', 'waitlist'])->default('pending');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['kids_event_id', 'registration_status']);
            $table->index('user_id');
            $table->index('parent_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kids_event_registrations');
    }
};

