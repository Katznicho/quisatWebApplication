<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $types = [
            ['name' => 'Certificate of Incorporation', 'description' => 'Official certificate issued at business registration.', 'account_type' => 'business', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'Copy of National ID/Passport', 'description' => null, 'account_type' => 'individual', 'is_required' => true, 'sort_order' => 1],
            ['name' => 'Memorandum of Association (MoA)', 'description' => "Document outlining the company's objectives and rules.", 'account_type' => 'business', 'is_required' => true, 'sort_order' => 2],
            ['name' => 'Shareholding Structure / Register', 'description' => 'List of all shareholders and their percentage ownership.', 'account_type' => 'business', 'is_required' => true, 'sort_order' => 3],
            ['name' => 'Copy of National ID/Passport of Directors', 'description' => 'Copy of national ID or passport for each director.', 'account_type' => 'business', 'is_required' => true, 'sort_order' => 4],
            ['name' => 'Regulatory / Operating License', 'description' => 'Required for regulated industries (e.g. financial services, healthcare).', 'account_type' => 'business', 'is_required' => false, 'sort_order' => 5],
            ['name' => 'Tax Identification Document', 'description' => 'TIN certificate or tax registration document.', 'account_type' => 'business', 'is_required' => false, 'sort_order' => 6],
            ['name' => 'Proof of Address', 'description' => 'Recent utility bill, bank statement, or any official document showing your business address (not older than 3 months).', 'account_type' => 'both', 'is_required' => true, 'sort_order' => 10],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->insert(array_merge($type, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('document_types')->whereIn('name', [
            'Certificate of Incorporation',
            'Copy of National ID/Passport',
            'Memorandum of Association (MoA)',
            'Shareholding Structure / Register',
            'Copy of National ID/Passport of Directors',
            'Regulatory / Operating License',
            'Tax Identification Document',
            'Proof of Address',
        ])->delete();
    }
};
