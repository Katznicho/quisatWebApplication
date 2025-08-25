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
        Schema::create('event_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('calendar_event_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Notification settings
            $table->string('title');
            $table->text('message');
            $table->enum('notification_type', ['email', 'sms', 'push', 'in_app'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            
            // Audience targeting
            $table->enum('target_type', ['all', 'specific_users', 'specific_roles', 'specific_classes', 'specific_students', 'specific_teachers', 'specific_parents'])->default('all');
            $table->json('target_ids')->nullable(); // Array of user IDs, role IDs, class IDs, etc.
            $table->json('target_filters')->nullable(); // Additional filters like grade level, department, etc.
            
            // Timing
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->integer('reminder_minutes')->nullable(); // Minutes before event to send reminder
            
            // Delivery tracking
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('delivery_log')->nullable(); // Detailed delivery status
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_notifications');
    }
};
