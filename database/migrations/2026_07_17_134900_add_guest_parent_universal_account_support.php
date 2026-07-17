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
        Schema::table('parent_guardians', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
        });

        Schema::table('parent_guardians', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->nullOnDelete();

            $table->enum('account_type', ['guest', 'linked'])->default('guest')->after('status');
            $table->string('universal_code', 32)->nullable()->unique()->after('account_type');
        });

        Schema::create('parent_guardian_business', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_guardian_id')
                ->constrained('parent_guardians')
                ->cascadeOnDelete();
            $table->foreignId('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->enum('joined_via', [
                'universal_code',
                'staff_invite',
                'staff_create',
                'clinic_attach',
                'self_join',
            ]);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_guardian_id', 'business_id'], 'parent_guardian_business_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_guardian_business');

        Schema::table('parent_guardians', function (Blueprint $table) {
            $table->dropUnique(['universal_code']);
            $table->dropColumn(['account_type', 'universal_code']);
            $table->dropForeign(['business_id']);
        });

        Schema::table('parent_guardians', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->cascadeOnDelete();
        });
    }
};
