<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Database\Seeders\QuisatSeeder;
use Database\Seeders\KidsChurchFeatureSeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call([
            QuisatSeeder::class,
            KidsChurchFeatureSeeder::class,
        ]);
    }
}
