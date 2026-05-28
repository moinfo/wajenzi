<?php

namespace Database\Seeders;

use App\Models\LandingAbout;
use App\Models\LandingTeamMember;
use App\Models\LandingValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the Landing CMS "About" section (singleton + core values + leadership
 * team) with the content previously hardcoded in the Flutter app
 * (about_screen.dart) and copies the bundled leadership photos into
 * storage/app/public/landing/team. Idempotent — skips when a landing_about
 * row already exists.
 */
class LandingAboutSeeder extends Seeder
{
    public function run(): void
    {
        if (LandingAbout::query()->exists()) {
            return;
        }

        $this->seedAbout();
        $this->seedValues();
        $this->seedTeam();
    }

    private function seedAbout(): void
    {
        $storyEn = implode("\n\n", [
            'Wajenzi Professional Co. Ltd is recognized as one of the leading construction companies in East Africa. Founded in 2012 by Engineer Eliya N Kishaluli and officially registered as a company limited in 2020, we have steadily grown to become an award-winning construction firm.',
            'With over 120 completed projects ranging from residential homes to commercial complexes, our commitment to quality and consistency has earned us recognition throughout the region. In 2024, Wajenzi Professional was honored with the Outstanding Residential Contractor of the Year award by the Chamber of Construction and Infrastructure of Tanzania (CCIT).',
            'Our company name "Wajenzi," which means "Builders" in Swahili, reflects our deep roots in the local culture and our commitment to building not just structures, but also relationships and communities.',
        ]);

        $storySw = implode("\n\n", [
            'Wajenzi Professional Co. Ltd inajulikana kama mojawapo ya makampuni yanayoongoza ya ujenzi Afrika Mashariki. Ilianzishwa mwaka 2012 na Mhandisi Eliya N Kishaluli na kusajiliwa rasmi kama kampuni yenye kikomo mwaka 2020, tumekua kuwa kampuni ya ujenzi yenye tuzo.',
            'Tukiwa na miradi zaidi ya 120 iliyokamilika kuanzia nyumba za makazi hadi majengo ya kibiashara, dhamira yetu ya ubora na uthabiti imetupata kutambuliwa kote katika eneo hili. Mwaka 2024, Wajenzi Professional ilituzwa tuzo ya Mkandarasi Bora wa Nyumba wa Mwaka na Chama cha Ujenzi na Miundombinu cha Tanzania (CCIT).',
            'Jina la kampuni yetu "Wajenzi," ambalo linamaanisha "Builders" kwa Kiingereza, linaonyesha mizizi yetu ya ndani katika utamaduni wa hapa na dhamira yetu ya kujenga si tu majengo, bali pia mahusiano na jamii.',
        ]);

        LandingAbout::create([
            'founded_year' => '2012',
            'tagline' => [
                'en' => 'Building Dreams, Creating Reality',
                'sw' => 'Kujenga Ndoto, Kuunda Uhalisia',
            ],
            'story' => [
                'en' => $storyEn,
                'sw' => $storySw,
            ],
            'mission' => [
                'en' => 'To deliver affordable, sustainable, and high-quality construction and architectural solutions that exceed client expectations while maintaining the highest standards of integrity, professionalism, and environmental responsibility.',
                'sw' => 'Kutoa suluhisho la ujenzi na usanifu bora, la bei nafuu, na endelevu ambalo linazidi matarajio ya wateja huku tukidumisha viwango vya juu vya uaminifu, taaluma, na uwajibikaji wa mazingira.',
            ],
            'vision' => [
                'en' => 'To become a leading global provider of innovative construction and design services, recognized for excellence, sustainability, and transformative impact on the built environment.',
                'sw' => 'Kuwa mtoa huduma wa kimataifa anayeongoza wa huduma za ujenzi na usanifu za ubunifu, zinazotambuliwa kwa ubora, uendelevu, na athari za mabadiliko katika mazingira yaliyojengwa.',
            ],
            'address' => 'Ground-Floor (07), PSSSF Commercial Complex, Dar es Salaam',
            'phone' => '+255 793 444 400',
            'email' => 'info@wajenziprofessional.co.tz',
            'working_hours' => [
                'en' => 'Mon - Fri: 8:00 AM - 6:00 PM',
                'sw' => 'Jumatatu - Ijumaa: 8:00 AM - 6:00 PM',
            ],
        ]);
    }

    private function seedValues(): void
    {
        foreach ($this->values() as $index => $v) {
            LandingValue::create([
                'title' => $v['title'],
                'description' => $v['description'],
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function values(): array
    {
        return [
            [
                'title' => ['en' => 'PRAYERS', 'sw' => 'MAOMBI'],
                'description' => [
                    'en' => 'We believe in the power of prayer and faith to guide our actions, unite our team, and inspire excellence in all our endeavors.',
                    'sw' => 'Tunaamini katika nguvu ya maombi na imani kuongoza matendo yetu, kuunganisha timu yetu, na kuhamasisha ubora katika juhudi zetu zote.',
                ],
            ],
            [
                'title' => ['en' => 'INNOVATION', 'sw' => 'UBUNIFU'],
                'description' => [
                    'en' => 'We develop and incorporate new technology and approaches to provide cutting-edge solutions for our clients\' construction and design needs.',
                    'sw' => 'Tunaendeleza na kuingiza teknolojia na mbinu mpya kutoa suluhisho za kisasa kwa mahitaji ya ujenzi na usanifu ya wateja wetu.',
                ],
            ],
            [
                'title' => ['en' => 'QUALITY', 'sw' => 'UBORA'],
                'description' => [
                    'en' => 'We believe it\'s the best thing to do one thing really really well. Our uncompromising commitment to excellence is reflected in every detail of our work.',
                    'sw' => 'Tunaamini ni jambo bora kufanya kitu kimoja vizuri sana. Kujitolea kwetu bila kusita kwa ubora kunaonyeshwa katika kila undani wa kazi yetu.',
                ],
            ],
            [
                'title' => ['en' => 'READING', 'sw' => 'KUSOMA'],
                'description' => [
                    'en' => 'We embrace reading as a tool for growth, knowledge, and continuous improvement. Our team is encouraged to constantly learn.',
                    'sw' => 'Tunakumbatia kusoma kama chombo cha ukuaji, maarifa, na uboreshaji wa kuendelea. Timu yetu inahimizwa kujifunza daima.',
                ],
            ],
            [
                'title' => ['en' => 'TEAMWORK', 'sw' => 'USHIRIKIANO'],
                'description' => [
                    'en' => 'We believe the best solution comes from working together. We foster a collaborative environment where diverse skills and perspectives are valued.',
                    'sw' => 'Tunaamini suluhisho bora linakuja kutokana na kufanya kazi pamoja. Tunakuza mazingira ya ushirikiano ambapo ujuzi na mtazamo mbalimbali unathaminiwa.',
                ],
            ],
            [
                'title' => ['en' => 'INTEGRITY', 'sw' => 'UAMINIFU'],
                'description' => [
                    'en' => 'We do the right thing always. Our business practices are founded on honesty, transparency, and ethical conduct.',
                    'sw' => 'Tunafanya jambo sahihi daima. Mazoea yetu ya biashara yanajengwa juu ya uaminifu, uwazi, na mwenendo wa kimaadili.',
                ],
            ],
        ];
    }

    private function seedTeam(): void
    {
        $sourceDir = base_path('wajenzi_mobile/assets/images');
        $destDirRel = 'landing/team';
        $destDirAbs = storage_path('app/public/' . $destDirRel);

        if (!File::exists($destDirAbs)) {
            File::makeDirectory($destDirAbs, 0755, true);
        }

        foreach ($this->team() as $index => $m) {
            $imagePath = null;
            $source = $sourceDir . '/' . $m['image'];
            if (File::exists($source)) {
                $fileName = ($index + 1) . '_' . str_replace([' ', '/'], '_', $m['image']);
                File::copy($source, $destDirAbs . '/' . $fileName);
                $imagePath = '/storage/' . $destDirRel . '/' . $fileName;
            }

            LandingTeamMember::create([
                'name' => $m['name'],
                'role' => $m['role'],
                'bio' => $m['bio'],
                'image' => $imagePath,
                'is_published' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function team(): array
    {
        return [
            [
                'name' => 'Eng. ELIYA N. KISHALULI',
                'role' => ['en' => 'Founder & CEO', 'sw' => 'Mwanzilishi na Mkurugenzi Mtendaji'],
                'bio' => [
                    'en' => 'With over 15 years of experience in construction and engineering, Eng. Eliya founded Wajenzi Professional with a vision to transform the construction industry in East Africa.',
                    'sw' => 'Akiwa na uzoefu wa zaidi ya miaka 15 katika ujenzi na uhandisi, Eng. Eliya alianzisha Wajenzi Professional akiwa na maono ya kubadilisha sekta ya ujenzi Afrika Mashariki.',
                ],
                'image' => 'ELIYA_KISHALULI.jpeg',
            ],
            [
                'name' => 'SARAH JOHN',
                'role' => ['en' => 'Managing Director', 'sw' => 'Mkurugenzi Msimamizi'],
                'bio' => [
                    'en' => 'Experienced Managing Director in construction, skilled in project management, budgeting, and team leadership, with a proven record of delivering quality projects on time and within budget.',
                    'sw' => 'Mkurugenzi Msimamizi mwenye uzoefu katika ujenzi, mzuri katika usimamizi wa miradi, bajeti, na uongozi wa timu, na rekodi iliyothibitishwa ya kutoa miradi bora kwa wakati na ndani ya bajeti.',
                ],
                'image' => 'SARAH_JOHN.png',
            ],
            [
                'name' => 'MOHAMEDI JOSEPH',
                'role' => ['en' => 'Business Development Manager', 'sw' => 'Meneja wa Maendeleo ya Biashara'],
                'bio' => [
                    'en' => 'Business Development Manager in the construction industry, skilled in client relations, project acquisition, and strategic growth, with a proven record of driving revenue and delivering successful projects.',
                    'sw' => 'Meneja wa Maendeleo ya Biashara katika sekta ya ujenzi, mzuri katika mahusiano na wateja, upataji wa miradi, na ukuaji wa kimkakati, na rekodi iliyothibitishwa ya kuongeza mapato.',
                ],
                'image' => 'MOHAMED_JOSEPH.png',
            ],
        ];
    }
}
