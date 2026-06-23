<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('total_ratings')->default(0);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('total_ratings')->default(0);
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hub', 32)->default('kidz_mart');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('approved');
            $table->boolean('verified_purchase')->default(false);
            $table->string('reviewer_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'order_item_id']);
            $table->index(['product_id', 'status']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('business_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hub', 32)->default('kidz_mart');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('approved');
            $table->boolean('verified_purchase')->default(false);
            $table->string('reviewer_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'order_id']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_reviews');
        Schema::dropIfExists('product_reviews');

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['rating', 'total_ratings']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating', 'total_ratings']);
        });
    }
};
