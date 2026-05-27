<?php

namespace Database\Seeders;

use App\Models\LandingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the Landing CMS services with the content previously hardcoded in the
 * Flutter app (services_screen.dart) and copies the bundled service images into
 * storage/app/public/landing/services. Idempotent (matches by English title).
 */
class LandingServicesSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = base_path('wajenzi_mobile/assets/images');
        $destDirRel = 'landing/services';
        $destDirAbs = storage_path('app/public/' . $destDirRel);

        if (!File::exists($destDirAbs)) {
            File::makeDirectory($destDirAbs, 0755, true);
        }

        foreach ($this->services() as $index => $s) {
            $exists = LandingService::all()->first(
                fn (LandingService $x) => ($x->title['en'] ?? null) === $s['title']['en']
            );
            if ($exists) {
                continue;
            }

            $imagePath = null;
            $source = $sourceDir . '/' . $s['image'];
            if (File::exists($source)) {
                $fileName = ($index + 1) . '_' . str_replace([' ', '/'], '_', $s['image']);
                File::copy($source, $destDirAbs . '/' . $fileName);
                $imagePath = '/storage/' . $destDirRel . '/' . $fileName;
            }

            LandingService::create([
                'title' => $s['title'],
                'short_description' => $s['short_description'],
                'full_description' => $s['full_description'],
                'image' => $imagePath,
                'features' => $s['features'],
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function services(): array
    {
        return [
            [
                'title' => ['en' => 'Construction Work', 'sw' => 'Kazi za Ujenzi', 'fr' => 'Travaux de construction', 'ar' => 'أعمال البناء'],
                'short_description' => ['en' => 'Expert construction services for residential, commercial, and institutional projects with attention to detail and quality.', 'sw' => 'Huduma za ujenzi wa kitaalamu kwa miradi ya makazi, biashara, na taasisi kwa umakini na ubora.'],
                'full_description' => ['en' => 'Our experienced team of builders provides comprehensive construction services from foundation to completion. We handle all project types - from single-family homes to multi-story buildings and commercial complexes.'],
                'image' => 'construction_01.png',
                'features' => ['Residential Construction', 'Commercial Buildings', 'Renovations & Extensions', 'Project Management'],
            ],
            [
                'title' => ['en' => 'Architectural Design', 'sw' => 'Usanifu wa Majengo', 'fr' => 'Conception architecturale', 'ar' => 'التصميم المعماري'],
                'short_description' => ['en' => 'Creative and functional architectural designs that transform your vision into reality with sustainability in mind.'],
                'full_description' => ['en' => 'Our architects blend creativity with functionality to create stunning and practical spaces. Every design is optimized for your lifestyle, budget, and environmental impact.'],
                'image' => 'NEW_1 - Photo.jpg',
                'features' => ['2D & 3D Designs', 'Interior Planning', 'Landscape Design', 'Sustainable Design'],
            ],
            [
                'title' => ['en' => 'Bill of Quantity', 'sw' => 'Orodha ya Vifaa', 'fr' => 'Métré quantitatif', 'ar' => 'جدول الكميات'],
                'short_description' => ['en' => 'Accurate cost estimation services to help you plan and budget your construction project effectively.'],
                'full_description' => ['en' => 'Our detailed BOQ estimates give you a complete picture of your project costs. We itemize all materials, labor, and expenses so you can plan and budget accurately.'],
                'image' => 'bill _of_quanties_01.png',
                'features' => ['Cost Estimation', 'Material Listing', 'Labor Analysis', 'Budget Planning'],
            ],
            [
                'title' => ['en' => 'Structural Design', 'sw' => 'Usanifu wa Miundo', 'fr' => 'Conception structurelle', 'ar' => 'التصميم الإنشائي'],
                'short_description' => ['en' => 'Expert structural engineering services that ensure your buildings are safe, stable, and built to last.'],
                'full_description' => ['en' => 'Our structural engineers perform thorough analysis and design to ensure your building can safely withstand all loads and environmental conditions.'],
                'image' => 'structure_01.jpg',
                'features' => ['Structural Analysis', 'Foundation Design', 'Load Calculations', 'Safety Assessment'],
            ],
            [
                'title' => ['en' => 'Interior Design', 'sw' => 'Muundo wa Ndani', 'fr' => 'Design intérieur', 'ar' => 'التصميم الداخلي'],
                'short_description' => ['en' => 'Transform your interior spaces into beautiful and functional environments that reflect your personal style.'],
                'full_description' => ['en' => 'Our interior design team creates spaces that combine aesthetics with functionality. We handle everything from color selection and furniture to lighting and decor.'],
                'image' => 'post/Screenshot 2026-01-21 at 14.50.28.png',
                'features' => ['Space Planning', 'Furniture Selection', 'Lighting Design', 'Color Consultation'],
            ],
            [
                'title' => ['en' => 'Construction Consultation', 'sw' => 'Ushauri wa Ujenzi', 'fr' => 'Conseil en construction', 'ar' => 'الاستشارات الإنشائية'],
                'short_description' => ['en' => 'Professional consultation for your construction projects from planning stage to completion.'],
                'full_description' => ['en' => 'Our experts provide comprehensive consultation on your construction projects. We help you understand options, minimize risks, and make informed decisions.'],
                'image' => 'post/Screenshot 2026-01-21 at 14.51.07.png',
                'features' => ['Project Assessment', 'Budget Advisory', 'Quality Inspection', 'Risk Management'],
            ],
        ];
    }
}
