<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('business_category_id')->constrained('countries')->nullOnDelete();
            $table->string('currency_code', 10)->nullable()->after('country_id');
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_code');
        });

        // Seed/ensure Uganda as the default country at migration time.
        $uganda = Country::firstOrCreate(
            ['name' => 'Uganda'],
            [
                'currency_code' => 'UGX',
                'currency_name' => 'Ugandan Shilling',
                'exchange_rate' => 1,
                'is_default' => true,
            ]
        );

        if (! $uganda->is_default) {
            $uganda->is_default = true;
            $uganda->save();
        }

        // Keep superadmin business (id=1) nullable, but set others to Uganda if missing.
        DB::table('businesses')
            ->where('id', '!=', 1)
            ->whereNull('country_id')
            ->update([
                'country_id' => $uganda->id,
                'country' => DB::raw("COALESCE(country, 'Uganda')"),
                'currency_code' => DB::raw("COALESCE(currency_code, 'UGX')"),
                'exchange_rate' => DB::raw('COALESCE(exchange_rate, 1)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn('currency_code');
            $table->dropColumn('exchange_rate');
        });
    }
};
