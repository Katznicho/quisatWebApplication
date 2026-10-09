<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_adverts', function (Blueprint $table) {
            $table->id();
            $table->string('advertiser_name');
            $table->string('logo_path')->nullable();
            $table->string('creative_type', 20);
            $table->string('creative_path');
            $table->string('destination_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64)->default('Africa/Nairobi');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('login_advert_state', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_login_advert_id')->nullable();
            $table->timestamps();
        });

        DB::table('login_advert_state')->insert([
            'id' => 1,
            'last_login_advert_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('login_advert_state');
        Schema::dropIfExists('login_adverts');
    }
};
