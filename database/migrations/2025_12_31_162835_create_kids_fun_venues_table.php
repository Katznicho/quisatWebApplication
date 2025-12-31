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
        Schema::create('kids_fun_venues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location');
            $table->time('open_time');
            $table->time('close_time');
            $table->json('activities')->nullable(); // Array of activities/services offered
            $table->json('prices')->nullable(); // Array of pricing information
            $table->json('images')->nullable(); // Array of image paths
            $table->string('website_link')->nullable();
            $table->json('social_media_handles')->nullable(); // {'facebook': 'url', 'instagram': 'url', etc.}
            $table->string('booking_link')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kids_fun_venues');
    }
};
