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
        Schema::table('program_events', function (Blueprint $table) {
            $table->enum('registration_method', ['link', 'list'])->nullable()->after('video');
            $table->string('registration_link')->nullable()->after('registration_method');
            $table->json('registration_list')->nullable()->after('registration_link'); // For list-based registration
            $table->json('social_media_handles')->nullable()->after('registration_list');
            $table->string('organizer_name')->nullable()->after('social_media_handles');
            $table->string('organizer_email')->nullable()->after('organizer_name');
            $table->string('organizer_phone')->nullable()->after('organizer_email');
            $table->text('organizer_address')->nullable()->after('organizer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_events', function (Blueprint $table) {
            $table->dropColumn([
                'registration_method',
                'registration_link',
                'registration_list',
                'social_media_handles',
                'organizer_name',
                'organizer_email',
                'organizer_phone',
                'organizer_address',
            ]);
        });
    }
};
