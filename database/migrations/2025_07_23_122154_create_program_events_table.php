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
        Schema::create('program_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            //program ids
            $table->json('program_ids');
            $table->string('name');
            $table->string('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('price')->default(0);
            $table->string('status');
            $table->string('location');
            //currency id
            $table->foreignId('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            //business id
            $table->foreignId('business_id')->references('id')->on('businesses')->onDelete('cascade');
            //user id
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_events');
    }
};
