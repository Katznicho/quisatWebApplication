<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Christian Kids Hub is platform content. Ensure the system business is Uganda-based.
     */
    public function up(): void
    {
        $uganda = DB::table('countries')
            ->where('is_default', true)
            ->orWhereRaw('LOWER(name) = ?', ['uganda'])
            ->orderByDesc('is_default')
            ->first();

        if (! $uganda) {
            return;
        }

        DB::table('businesses')
            ->where('id', 1)
            ->update([
                'country_id' => $uganda->id,
                'country' => $uganda->name,
                'city' => DB::raw("CASE WHEN city IS NULL OR city = '' OR city = 'System City' THEN 'Kampala' ELSE city END"),
                'updated_at' => now(),
            ]);

        if (DB::table('businesses')->where('id', 1)->exists()) {
            DB::table('program_events')
                ->update([
                    'business_id' => 1,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: platform country assignment should remain.
    }
};
