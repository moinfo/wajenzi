<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencyRatesSeeder extends Seeder
{
    public function run(): void
    {
        // Wipe and rebuild cleanly to remove any duplicates or incomplete rows
        DB::table('currencies')->truncate();

        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro',                 'symbol' => '€',   'rate_to_usd' => 0.92,   'is_base' => false, 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound',        'symbol' => '£',   'rate_to_usd' => 0.79,   'is_base' => false, 'is_active' => true],
            ['code' => 'KES', 'name' => 'Kenyan Shilling',      'symbol' => 'KSh', 'rate_to_usd' => 129.5,  'is_base' => false, 'is_active' => true],
            ['code' => 'TZS', 'name' => 'Tanzanian Shillings',  'symbol' => 'TZS', 'rate_to_usd' => 2640.0, 'is_base' => true,  'is_active' => true],
            ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$',   'rate_to_usd' => 1.0,    'is_base' => false, 'is_active' => true],
        ];

        foreach ($currencies as $c) {
            DB::table('currencies')->insert(array_merge($c, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Currencies seeded: EUR, GBP, KES, TZS (2640), USD');
    }
}

