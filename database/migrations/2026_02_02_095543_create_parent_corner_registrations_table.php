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
        Schema::create('parent_corner_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_corner_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // nullable for guest registrations
            
            // Parent information
            $table->string('parent_name');
            $table->string('parent_email');
            $table->string('parent_phone');
            $table->string('parent_address')->nullable();
            $table->integer('number_of_children')->nullable();
            
            // Additional information
            $table->text('interests')->nullable(); // Topics of interest
            $table->text('notes')->nullable();
            
            // Payment information
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'airtel_money', 'mtn_mobile_money', 'other'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Registration status
            $table->enum('registration_status', ['pending', 'confirmed', 'cancelled', 'waitlist'])->default('pending');
            
            $table->timestamps();
            
            // Indexes (using shorter names to avoid MySQL 64 char limit)
            $table->index(['parent_corner_id', 'registration_status'], 'pc_reg_parent_corner_status_idx');
            $table->index('user_id', 'pc_reg_user_id_idx');
            $table->index('parent_email', 'pc_reg_email_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_corner_registrations');
    }
};
