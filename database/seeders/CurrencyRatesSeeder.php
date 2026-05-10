<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencyRatesSeeder extends Seeder
{
    public function run(): void
    {
        // Update existing TZS (id=1) and USD (id=2)
        DB::table('currencies')->where('id', 1)->update([
            'code'        => 'TZS',
            'rate_to_usd' => 2640,
            'is_active'   => true,
        ]);

        DB::table('currencies')->where('id', 2)->update([
            'code'        => 'USD',
            'rate_to_usd' => 1.0,
            'is_active'   => true,
        ]);

        // Add additional currencies
        $currencies = [
            ['name' => 'Euro',            'symbol' => '€',  'code' => 'EUR', 'rate_to_usd' => 0.92,  'is_base' => 'NO', 'is_active' => true],
            ['name' => 'Kenyan Shilling', 'symbol' => 'KSh','code' => 'KES', 'rate_to_usd' => 129.5, 'is_base' => 'NO', 'is_active' => true],
            ['name' => 'British Pound',   'symbol' => '£',  'code' => 'GBP', 'rate_to_usd' => 0.79,  'is_base' => 'NO', 'is_active' => true],
        ];

        foreach ($currencies as $c) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $c['code']],
                array_merge($c, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('Currency rates seeded: TZS, USD, EUR, KES, GBP');
    }
}
