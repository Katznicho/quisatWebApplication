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
        Schema::create('kids_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('host_organization');
            $table->string('category')->default('Educational');
            $table->string('location')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('max_participants')->nullable();
            $table->integer('current_participants')->default(0);
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('status')->default('upcoming'); // upcoming, ongoing, completed, cancelled
            $table->boolean('requires_parent_permission')->default(false);
            $table->string('image_url')->nullable();
            $table->json('target_age_groups')->nullable(); // e.g., ["5-8", "9-12", "13-16"]
            $table->json('requirements')->nullable(); // e.g., ["Bring water bottle", "Wear comfortable clothes"]
            $table->text('contact_info')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('rating', 3, 2)->nullable(); // 0.00 to 5.00
            $table->integer('total_ratings')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_external')->default(true); // External events vs internal school events
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kids_events');
    }
};
