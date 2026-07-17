<?php

namespace App\Console\Commands;

use App\Models\ParentGuardian;
use App\Services\ParentUniversalCodeService;
use Illuminate\Console\Command;

class BackfillParentUniversalCodes extends Command
{
    protected $signature = 'parents:backfill-universal-codes
                            {--dry-run : Show what would be updated without writing}';

    protected $description = 'Backfill parent_guardian_business memberships, account_type=linked, and QSP universal codes';

    public function handle(ParentUniversalCodeService $codes): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $memberships = 0;
        $codesAssigned = 0;
        $linked = 0;

        ParentGuardian::withTrashed()
            ->orderBy('id')
            ->chunkById(200, function ($parents) use ($codes, $dryRun, &$memberships, &$codesAssigned, &$linked) {
                foreach ($parents as $parent) {
                    if ($parent->business_id) {
                        $exists = $parent->memberships()
                            ->where('business_id', $parent->business_id)
                            ->exists();

                        if (! $exists) {
                            if (! $dryRun) {
                                $codes->attachToBusiness(
                                    $parent,
                                    (int) $parent->business_id,
                                    'staff_create',
                                    $parent->relationship
                                );
                                $parent->refresh();
                            }
                            $memberships++;
                        } elseif ($parent->account_type !== 'linked') {
                            if (! $dryRun) {
                                $parent->forceFill(['account_type' => 'linked'])->save();
                            }
                            $linked++;
                        }
                    }

                    if (empty($parent->universal_code)) {
                        if (! $dryRun) {
                            $codes->ensureCode($parent);
                        }
                        $codesAssigned++;
                    }
                }
            });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Memberships ensured: {$memberships}");
        $this->info("{$prefix}Universal codes assigned: {$codesAssigned}");
        $this->info("{$prefix}Account type set to linked (existing membership): {$linked}");

        return self::SUCCESS;
    }
}
