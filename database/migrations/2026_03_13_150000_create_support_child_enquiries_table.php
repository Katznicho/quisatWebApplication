<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_child_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_child_id')->constrained('support_children')->onDelete('cascade');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('preferred_contact_method')->nullable(); // phone, email, any
            $table->text('message')->nullable();
            $table->string('source')->nullable(); // app_parent, app_staff, app_guest, web
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_child_enquiries');
    }
};

