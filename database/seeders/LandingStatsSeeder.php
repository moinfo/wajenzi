<?php

namespace Database\Seeders;

use App\Models\LandingStat;
use Illuminate\Database\Seeder;

/**
 * Seeds the four hero stats previously hardcoded in the Flutter landing screen.
 * Idempotent (matches by English label).
 */
class LandingStatsSeeder extends Seeder
{
    public function run(): void
    {
        $stats = [
            ['value' => '120+', 'label' => ['en' => 'Flagship Projects', 'sw' => 'Miradi ya Kipekee']],
            ['value' => '50+', 'label' => ['en' => 'Experts', 'sw' => 'Wataalamu']],
            ['value' => '200+', 'label' => ['en' => 'Completed', 'sw' => 'Imekamilika']],
            ['value' => '4.9', 'label' => ['en' => 'Rating', 'sw' => 'Ukadiriaji']],
        ];

        foreach ($stats as $index => $s) {
            $exists = LandingStat::all()->first(
                fn (LandingStat $x) => ($x->label['en'] ?? null) === $s['label']['en']
            );
            if ($exists) {
                continue;
            }
            LandingStat::create([
                'value' => $s['value'],
                'label' => $s['label'],
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
