<?php

namespace Database\Seeders;

use App\Models\LandingProject;
use App\Models\LandingProjectAmenity;
use App\Models\LandingProjectImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the Landing CMS portfolio with the content that was previously
 * hardcoded in the Flutter app (lib/presentation/screens/landing/landing_screen.dart),
 * and copies the bundled showcase images from the mobile assets into
 * storage/app/public/landing/portfolio so the CMS launches pre-populated.
 *
 * Idempotent: matches existing rows by English title before inserting.
 */
class LandingPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = base_path('wajenzi_mobile/assets/images/post');
        $destDirRel = 'landing/portfolio';                       // relative to storage/app/public
        $destDirAbs = storage_path('app/public/' . $destDirRel);

        if (!File::exists($destDirAbs)) {
            File::makeDirectory($destDirAbs, 0755, true);
        }

        foreach ($this->projects() as $index => $p) {
            // Skip if a project with this English title already exists.
            $exists = LandingProject::all()->first(
                fn (LandingProject $lp) => ($lp->title['en'] ?? null) === $p['title']['en']
            );
            if ($exists) {
                continue;
            }

            $project = LandingProject::create([
                'title' => $p['title'],
                'category' => $p['category'],
                'description' => $p['description'],
                'price_tzs' => $p['price_tzs'],
                'price_usd' => $p['price_usd'],
                'is_featured' => $p['is_featured'] ?? false,
                'is_published' => true,
                'sort_order' => $index,
            ]);

            // Copy the source image into storage and attach as the primary image.
            $sourcePath = $sourceDir . '/' . $p['image'];
            if (File::exists($sourcePath)) {
                $fileName = $project->id . '_' . str_replace(' ', '_', $p['image']);
                File::copy($sourcePath, $destDirAbs . '/' . $fileName);
                LandingProjectImage::create([
                    'landing_project_id' => $project->id,
                    'file' => '/storage/' . $destDirRel . '/' . $fileName,
                    'file_name' => $p['image'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }

            foreach ($p['amenities'] as $aIndex => $label) {
                LandingProjectAmenity::create([
                    'landing_project_id' => $project->id,
                    'label' => is_array($label) ? $label : ['en' => $label],
                    'sort_order' => $aIndex,
                ]);
            }
        }
    }

    /**
     * The 6 projects, mirrored from the previously hardcoded Dart data.
     * Translations are included where they existed; English-first otherwise.
     */
    private function projects(): array
    {
        $cat3d = ['en' => '3D Design', 'sw' => 'Ubunifu wa 3D', 'fr' => 'Design 3D', 'ar' => 'تصميم ثلاثي الأبعاد'];
        $catDone = ['en' => 'Completed', 'sw' => 'Imekamilika', 'fr' => 'Terminé', 'ar' => 'مكتمل'];
        $catProgress = ['en' => 'In Progress', 'sw' => 'Inaendelea', 'fr' => 'En cours', 'ar' => 'قيد التنفيذ'];
        $catDesign = ['en' => 'Design', 'sw' => 'Ubunifu', 'fr' => 'Conception', 'ar' => 'تصميم'];

        return [
            [
                'image' => 'Screenshot 2026-01-21 at 14.50.10.png',
                'title' => ['en' => 'Hotel Construction', 'sw' => 'Ujenzi wa Hoteli', 'fr' => "Construction d’hôtel", 'ar' => 'بناء فندق'],
                'category' => $cat3d,
                'price_tzs' => 6911200000,
                'price_usd' => 2764480,
                'description' => [
                    'en' => 'Luxury hotel project featuring modern architecture with premium amenities.',
                    'sw' => 'Mradi wa hoteli ya kifahari wenye usanifu wa kisasa na huduma za kiwango cha juu.',
                    'fr' => 'Projet hôtelier de luxe présentant une architecture moderne et des équipements haut de gamme.',
                    'ar' => 'مشروع فندق فاخر يتميز بعمارة حديثة ومرافق متميزة.',
                ],
                'amenities' => [
                    ['en' => 'Bedrooms', 'sw' => 'Vyumba', 'fr' => 'Chambres', 'ar' => 'غرف النوم'],
                    ['en' => 'Restaurant', 'sw' => 'Mkahawa', 'fr' => 'Restaurant', 'ar' => 'مطعم'],
                    ['en' => 'Bar', 'sw' => 'Baa', 'fr' => 'Bar', 'ar' => 'بار'],
                    ['en' => 'Parking', 'sw' => 'Maegesho', 'fr' => 'Parking', 'ar' => 'مواقف'],
                    ['en' => 'Gym'],
                    ['en' => 'Spa'],
                ],
                'is_featured' => true,
            ],
            [
                'image' => 'Screenshot 2026-01-21 at 14.50.20.png',
                'title' => ['en' => 'Residential Villa', 'sw' => 'Villa ya Makazi', 'fr' => 'Villa résidentielle', 'ar' => 'فيلا سكنية'],
                'category' => $catDone,
                'price_tzs' => 850000000,
                'price_usd' => 340000,
                'description' => [
                    'en' => 'Beautiful modern villa in Dar es Salaam with stunning views.',
                    'sw' => 'Villa nzuri ya kisasa jijini Dar es Salaam yenye mandhari ya kuvutia.',
                    'fr' => 'Belle villa moderne à Dar es Salaam avec des vues imprenables.',
                    'ar' => 'فيلا حديثة جميلة في دار السلام بإطلالات خلابة.',
                ],
                'amenities' => [['en' => '5 Bedrooms'], ['en' => 'Swimming Pool'], ['en' => 'Garden'], ['en' => 'Garage']],
            ],
            [
                'image' => 'Screenshot 2026-01-21 at 14.50.28.png',
                'title' => ['en' => 'Office Complex'],
                'category' => $catProgress,
                'price_tzs' => 2500000000,
                'price_usd' => 1000000,
                'description' => ['en' => 'State-of-the-art commercial office building in the business district.'],
                'amenities' => [['en' => 'Open Offices'], ['en' => 'Meeting Rooms'], ['en' => 'Cafeteria'], ['en' => 'Parking']],
            ],
            [
                'image' => 'Screenshot 2026-01-21 at 14.50.31.png',
                'title' => ['en' => 'Apartment Complex'],
                'category' => $catDesign,
                'price_tzs' => 4200000000,
                'price_usd' => 1680000,
                'description' => ['en' => 'Modern apartment living with premium shared amenities.'],
                'amenities' => [['en' => '24 Units'], ['en' => 'Gym'], ['en' => 'Rooftop Lounge'], ['en' => 'Security']],
            ],
            [
                'image' => 'Screenshot 2026-01-21 at 14.50.40.png',
                'title' => ['en' => 'Shopping Mall'],
                'category' => $cat3d,
                'price_tzs' => 12500000000,
                'price_usd' => 5000000,
                'description' => ['en' => 'Modern shopping center with entertainment and retail facilities.'],
                'amenities' => [['en' => '150 Shops'], ['en' => 'Cinema'], ['en' => 'Food Court'], ['en' => 'Parking']],
            ],
            [
                'image' => 'Screenshot 2026-01-21 at 14.51.07.png',
                'title' => ['en' => 'School Building'],
                'category' => $catDone,
                'price_tzs' => 1800000000,
                'price_usd' => 720000,
                'description' => ['en' => 'Educational facility with modern learning environments.'],
                'amenities' => [['en' => '30 Classrooms'], ['en' => 'Library'], ['en' => 'Labs'], ['en' => 'Sports Field']],
            ],
        ];
    }
}
