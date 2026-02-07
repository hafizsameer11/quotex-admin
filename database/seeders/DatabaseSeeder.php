<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run new seeders
        $this->call([
            TradingPairsSeeder::class,
            WalletTotalBalanceSeeder::class,
            LoyaltySeeder::class,
        ]);
    }
}
