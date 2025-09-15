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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->string('title');
            $table->text('description');
            $table->enum('media_type', ['image', 'video', 'text'])->default('image');
            $table->string('media_path')->nullable();
            $table->json('target_audience')->nullable(); // ['all_users', 'staff', 'students', 'parents']
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['daily', 'weekly', 'monthly'])->nullable();
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'expired'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->decimal('budget', 10, 2)->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};