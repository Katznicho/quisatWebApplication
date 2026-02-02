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
        Schema::create('parent_corners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('Workshop'); // Workshop, Seminar, Support Group, Training, etc.
            $table->string('location')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('max_participants')->nullable();
            $table->integer('current_participants')->default(0);
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('status')->default('draft'); // draft, published, completed, cancelled
            $table->string('image_url')->nullable();
            $table->text('contact_info')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('rating', 3, 2)->nullable(); // 0.00 to 5.00
            $table->integer('total_ratings')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->enum('registration_method', ['link', 'list'])->nullable();
            $table->string('registration_link')->nullable();
            $table->json('registration_list')->nullable();
            $table->json('social_media_handles')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('organizer_email')->nullable();
            $table->string('organizer_phone')->nullable();
            $table->text('organizer_address')->nullable();
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
        Schema::dropIfExists('parent_corners');
    }
};
