<?php

namespace Database\Seeders;

use App\Models\DesignServiceAddon;
use App\Models\DesignServicePackage;
use App\Models\DesignSpecialStructure;
use Illuminate\Database\Seeder;

class DesignPricingDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Add-ons ─────────────────────────────────────────────────────────
        $addons = [
            ['name' => 'Fence design',              'price_low_usd' => 120, 'price_high_usd' => 120, 'sort_order' => 1],
            ['name' => 'BOQ preparation',           'price_low_usd' => 120, 'price_high_usd' => 240, 'sort_order' => 2],
            ['name' => "Servant's quarter design",  'price_low_usd' => 150, 'price_high_usd' => 150, 'sort_order' => 3],
        ];

        foreach ($addons as $a) {
            DesignServiceAddon::updateOrCreate(['name' => $a['name']], array_merge($a, ['is_active' => true]));
        }

        // ── Packages — Low-rise ──────────────────────────────────────────────
        $lowPackages = [
            [
                'name' => 'SILVER', 'rise_type' => 'low', 'price_usd' => 320, 'sort_order' => 1,
                'included_services' => ['Architectural design'],
            ],
            [
                'name' => 'GOLD', 'rise_type' => 'low', 'price_usd' => 400, 'sort_order' => 2,
                'included_services' => ['Architectural design', 'BOQ preparation'],
            ],
            [
                'name' => 'PLATINUM', 'rise_type' => 'low', 'price_usd' => 580, 'sort_order' => 3,
                'included_services' => ['Architectural design', 'BOQ preparation', 'Fence design', "Servant's quarter design"],
            ],
        ];

        // ── Packages — High-rise ─────────────────────────────────────────────
        $highPackages = [
            [
                'name' => 'SILVER', 'rise_type' => 'high', 'price_usd' => 1000, 'sort_order' => 4,
                'included_services' => ['Architectural design', 'Structural design'],
            ],
            [
                'name' => 'GOLD', 'rise_type' => 'high', 'price_usd' => 1200, 'sort_order' => 5,
                'included_services' => ['Architectural design', 'Structural design', 'BOQ preparation'],
            ],
            [
                'name' => 'PLATINUM', 'rise_type' => 'high', 'price_usd' => 1400, 'sort_order' => 6,
                'included_services' => ['Architectural design', 'Structural design', 'BOQ preparation', 'Fence design', "Servant's quarter design"],
            ],
        ];

        foreach (array_merge($lowPackages, $highPackages) as $p) {
            DesignServicePackage::updateOrCreate(
                ['name' => $p['name'], 'rise_type' => $p['rise_type']],
                array_merge($p, ['is_active' => true])
            );
        }

        // ── Special structures ───────────────────────────────────────────────
        $structures = [
            ['name' => 'Warehouse',      'rate_tzs_per_sqm' => 5000,  'sort_order' => 1],
            ['name' => 'Swimming pool',  'rate_tzs_per_sqm' => 40000, 'sort_order' => 2],
            ['name' => 'Pergola',        'rate_tzs_per_sqm' => 30000, 'sort_order' => 3],
            ['name' => 'Playground',     'rate_tzs_per_sqm' => 5000,  'sort_order' => 4],
        ];

        foreach ($structures as $s) {
            DesignSpecialStructure::updateOrCreate(['name' => $s['name']], array_merge($s, ['is_active' => true]));
        }

        $this->command->info('Design pricing data seeded: 3 add-ons, 6 packages, 4 special structures.');
    }
}
