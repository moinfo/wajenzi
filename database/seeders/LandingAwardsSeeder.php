<?php

namespace Database\Seeders;

use App\Models\LandingAward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the Landing CMS awards with the content previously hardcoded in the
 * Flutter app (awards_screen.dart) and copies the bundled award images into
 * storage/app/public/landing/awards. Idempotent (matches by English title).
 */
class LandingAwardsSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = base_path('wajenzi_mobile/assets/images/awards');
        $destDirRel = 'landing/awards';
        $destDirAbs = storage_path('app/public/' . $destDirRel);

        if (!File::exists($destDirAbs)) {
            File::makeDirectory($destDirAbs, 0755, true);
        }

        foreach ($this->awards() as $index => $a) {
            $exists = LandingAward::all()->first(
                fn (LandingAward $x) => ($x->title['en'] ?? null) === $a['title']['en']
            );
            if ($exists) {
                continue;
            }

            $imagePath = null;
            $source = $sourceDir . '/' . $a['image'];
            if (File::exists($source)) {
                $fileName = ($index + 1) . '_' . $a['image'];
                File::copy($source, $destDirAbs . '/' . $fileName);
                $imagePath = '/storage/' . $destDirRel . '/' . $fileName;
            }

            LandingAward::create([
                'year' => $a['year'],
                'title' => $a['title'],
                'subtitle' => $a['subtitle'],
                'organization' => $a['organization'],
                'description' => $a['description'],
                'image' => $imagePath,
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function awards(): array
    {
        return [
            [
                'year' => '2024',
                'title' => ['en' => 'Outstanding Residential Contractor', 'sw' => 'Mkandarasi Bora wa Nyumba'],
                'subtitle' => ['en' => 'Outstanding Residential Contractor of the Year', 'sw' => 'Mkandarasi Bora wa Nyumba wa Mwaka'],
                'organization' => ['en' => 'Chamber of Construction and Infrastructure of Tanzania (CCIT)', 'sw' => 'Chama cha Ujenzi na Miundombinu cha Tanzania (CCIT)'],
                'description' => ['en' => 'Recognized for excellence in residential construction projects, delivering exceptional quality, innovation, and client satisfaction.', 'sw' => 'Kutambuliwa kwa ubora katika miradi ya ujenzi wa makazi, kutoa ubora wa kipekee, ubunifu, na kuridhika kwa wateja.'],
                'image' => 'BQ6A3834.jpeg',
            ],
            [
                'year' => '2023',
                'title' => ['en' => 'Excellence in Construction', 'sw' => 'Ubora katika Ujenzi', 'fr' => 'Excellence en construction', 'ar' => 'التميز في البناء'],
                'subtitle' => ['en' => 'Excellence in Construction Award', 'sw' => 'Tuzo ya Ubora katika Ujenzi'],
                'organization' => ['en' => 'East African Builders Association', 'sw' => 'Chama cha Wajenzi wa Afrika Mashariki'],
                'description' => ['en' => 'Awarded for demonstrating outstanding craftsmanship, technical innovation, and project management across multiple construction projects.', 'sw' => 'Kutuzwa kwa kuonyesha ufundi bora, ubunifu wa kiufundi, na usimamizi wa mradi katika miradi mingi ya ujenzi.'],
                'image' => 'BQ6A3837.jpeg',
            ],
            [
                'year' => '2022',
                'title' => ['en' => 'Sustainable Building', 'sw' => 'Ujenzi Endelevu', 'fr' => 'Construction durable', 'ar' => 'البناء المستدام'],
                'subtitle' => ['en' => 'Sustainable Building Leadership Award', 'sw' => 'Tuzo ya Uongozi wa Ujenzi Endelevu'],
                'organization' => ['en' => 'Green Building Council Tanzania', 'sw' => 'Baraza la Majengo ya Kijani Tanzania'],
                'description' => ['en' => 'Recognized for implementing sustainable practices, energy-efficient designs, and eco-friendly materials in construction projects.', 'sw' => 'Kutambuliwa kwa kutekeleza mazoea endelevu, miundo inayotumia nishati kwa ufanisi, na vifaa rafiki kwa mazingira katika miradi ya ujenzi.'],
                'image' => 'BQ6A3840.jpeg',
            ],
            [
                'year' => '2021',
                'title' => ['en' => 'Innovation Award', 'sw' => 'Tuzo ya Ubunifu', 'fr' => "Prix de l’innovation", 'ar' => 'جائزة الابتكار'],
                'subtitle' => ['en' => 'Innovation in Construction Award', 'sw' => 'Tuzo ya Ubunifu katika Ujenzi'],
                'organization' => ['en' => 'Tanzania Construction Innovation Council', 'sw' => 'Baraza la Ubunifu wa Ujenzi Tanzania'],
                'description' => ['en' => 'Recognized for implementing innovative construction techniques and sustainable building practices in residential projects.', 'sw' => 'Kutambuliwa kwa kutekeleza mbinu za ujenzi za ubunifu na mazoea endelevu ya ujenzi katika miradi ya makazi.'],
                'image' => 'BQ6A3837_2.jpeg',
            ],
        ];
    }
}
