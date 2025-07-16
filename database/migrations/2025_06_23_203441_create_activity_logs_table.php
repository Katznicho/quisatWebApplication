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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->references('id')->on('businesses')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->references('id')->on('branches')->onDelete('cascade');
            $table->string('model_type');        // e.g. App\Models\User
            $table->unsignedBigInteger('model_id')->nullable(); // e.g. User ID = 5
            $table->string('action');            // created / updated / deleted
            $table->json('old_values')->nullable(); // before update/delete
            $table->json('new_values')->nullable(); // after create/update
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('action_type')->nullable();
            $table->string("description")->nullable();
            $table->date('date')->default(now());
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
