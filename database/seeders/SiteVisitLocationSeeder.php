<?php

namespace Database\Seeders;

use App\Models\SiteVisitLocation;
use Illuminate\Database\Seeder;

class SiteVisitLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Preset values sourced from the approved site_visit_cost_calculator_dashboard.html
        // base_cost_tzs = one-time call-out fee per visit (not multiplied by days)
        $locations = [
            ['name' => 'Dar es Salaam', 'base_cost_tzs' => 150000, 'preset_travel_tzs' => 0,      'preset_local_tzs' => 0,     'preset_allowance_tzs' => 0,      'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 1],
            ['name' => 'Morogoro',      'base_cost_tzs' => 150000, 'preset_travel_tzs' => 30000,  'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 2],
            ['name' => 'Tanga',         'base_cost_tzs' => 150000, 'preset_travel_tzs' => 50000,  'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 3],
            ['name' => 'Dodoma',        'base_cost_tzs' => 150000, 'preset_travel_tzs' => 70000,  'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 4],
            ['name' => 'Zanzibar',      'base_cost_tzs' => 150000, 'preset_travel_tzs' => 70000,  'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 5],
            ['name' => 'Arusha',        'base_cost_tzs' => 150000, 'preset_travel_tzs' => 100000, 'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 6],
            ['name' => 'Singida',       'base_cost_tzs' => 150000, 'preset_travel_tzs' => 100000, 'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 50000,  'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 7],
            ['name' => 'Songea',        'base_cost_tzs' => 150000, 'preset_travel_tzs' => 140000, 'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 100000, 'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 8],
            ['name' => 'Mwanza',        'base_cost_tzs' => 150000, 'preset_travel_tzs' => 160000, 'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 100000, 'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 9],
            ['name' => 'Bukoba',        'base_cost_tzs' => 150000, 'preset_travel_tzs' => 190000, 'preset_local_tzs' => 10000, 'preset_allowance_tzs' => 100000, 'preset_food_tzs' => 0, 'preset_accommodation_tzs' => 0, 'sort_order' => 10],
        ];

        foreach ($locations as $loc) {
            SiteVisitLocation::updateOrCreate(['name' => $loc['name']], array_merge($loc, ['is_active' => true]));
        }

        $this->command->info('Site visit locations seeded: ' . count($locations) . ' locations.');
    }
}
