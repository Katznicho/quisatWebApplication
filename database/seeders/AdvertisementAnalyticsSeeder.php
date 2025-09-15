<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use Carbon\Carbon;

class AdvertisementAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all advertisements
        $advertisements = Advertisement::all();
        
        if ($advertisements->isEmpty()) {
            $this->command->info('No advertisements found. Please run AdvertisementSeeder first.');
            return;
        }
        
        $this->command->info('Creating sample analytics data for ' . $advertisements->count() . ' advertisements...');
        
        foreach ($advertisements as $advertisement) {
            // Create analytics data for the last 30 days
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Skip weekends for some variety
                if ($date->isWeekend() && rand(1, 3) === 1) {
                    continue;
                }
                
                // Generate realistic analytics data
                $impressions = rand(50, 500);
                $clicks = rand(1, max(1, intval($impressions * 0.02))); // 2% CTR
                $conversions = rand(0, max(0, intval($clicks * 0.1))); // 10% conversion rate
                $spend = rand(5, 50); // Random spend amount
                
                AdvertisementAnalytics::create([
                    'advertisement_id' => $advertisement->id,
                    'date' => $date,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'spend' => $spend,
                    'user_id' => null, // No specific user tracking for now
                    'interaction_type' => null // No specific interaction type for now
                ]);
            }
        }
        
        $this->command->info('Created analytics data for ' . $advertisements->count() . ' advertisements.');
    }
}