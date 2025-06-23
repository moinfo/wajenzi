<?php

namespace Database\Seeders;

use App\Models\VatPayment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         \App\Models\User::factory(10)->create();
        $this->call([
            MenusSeeder::class,
            CurrenciesSeeder::class,
            AddAllowanceSubscriptionMenu::class,
            AddPayrollMenuSeeder::class,
            AddSystemCashAndCapitalMenuSeeder::class,
            AddSystemCreditAndInventoryMenuSeeder::class,
            DeductionSettingsSeeder::class,
            DeductionsSeeder::class,
            AddVatPaymentMenuSeeder::class
        ]);
    }
}
