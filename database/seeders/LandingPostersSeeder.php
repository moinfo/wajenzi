<?php

namespace Database\Seeders;

use App\Models\LandingPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds one sample home banner so the poster carousel renders out of the box.
 * Uses an existing bundled hero image + the company tagline (real brand copy).
 * Idempotent (matches by English title).
 */
class LandingPostersSeeder extends Seeder
{
    public function run(): void
    {
        $title = 'Wajenzi Professionals';
        $exists = LandingPoster::all()->first(
            fn (LandingPoster $p) => ($p->title['en'] ?? null) === $title
        );
        if ($exists) {
            return;
        }

        $destDirRel = 'landing/posters';
        $destDirAbs = storage_path('app/public/' . $destDirRel);
        if (!File::exists($destDirAbs)) {
            File::makeDirectory($destDirAbs, 0755, true);
        }

        $source = base_path('wajenzi_mobile/assets/images/post/Screenshot 2026-01-21 at 14.50.10.png');
        $imagePath = null;
        if (File::exists($source)) {
            $fileName = 'sample_banner.png';
            File::copy($source, $destDirAbs . '/' . $fileName);
            $imagePath = '/storage/' . $destDirRel . '/' . $fileName;
        }

        if ($imagePath === null) {
            return; // no image available; skip rather than seed a broken banner
        }

        LandingPoster::create([
            'title' => ['en' => $title, 'sw' => 'Wajenzi Professionals'],
            'subtitle' => [
                'en' => 'Masters of Consistency and Quality',
                'sw' => 'Mabingwa wa Uthabiti na Ubora',
            ],
            'image' => $imagePath,
            'is_published' => true,
            'sort_order' => 0,
        ]);
    }
}
