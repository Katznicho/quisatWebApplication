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
        Schema::create('advertisement_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id');
            $table->date('date');
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('spend', 10, 2)->default(0);
            $table->unsignedBigInteger('user_id')->nullable(); // For tracking which user interacted
            $table->enum('interaction_type', ['view', 'click', 'conversion'])->nullable();
            $table->timestamps();

            $table->foreign('advertisement_id')->references('id')->on('advertisements')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['advertisement_id', 'date']);
            $table->index(['user_id', 'interaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisement_analytics');
    }
};