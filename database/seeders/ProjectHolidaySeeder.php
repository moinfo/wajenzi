<?php

namespace Database\Seeders;

use App\Models\ProjectHoliday;
use Illuminate\Database\Seeder;

class ProjectHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tanzania Public Holidays for 2026
        $holidays2026 = [
            ['date' => '2026-01-01', 'name' => "New Year's Day"],
            ['date' => '2026-01-12', 'name' => 'Zanzibar Revolutionary Day'],
            ['date' => '2026-03-20', 'name' => 'Eid al-Fitr'],
            ['date' => '2026-03-21', 'name' => 'Eid al-Fitr (Holiday)'],
            ['date' => '2026-04-03', 'name' => 'Good Friday'],
            ['date' => '2026-04-06', 'name' => 'Easter Monday'],
            ['date' => '2026-04-07', 'name' => 'Karume Day'],
            ['date' => '2026-04-26', 'name' => 'Union Day'],
            ['date' => '2026-05-01', 'name' => 'Labour Day'],
            ['date' => '2026-05-27', 'name' => 'Eid al-Hajj'],
            ['date' => '2026-07-07', 'name' => 'Saba Saba'],
            ['date' => '2026-08-08', 'name' => 'Nane Nane'],
            ['date' => '2026-08-25', 'name' => 'Maulid Day'],
            ['date' => '2026-10-14', 'name' => 'Nyerere Day'],
            ['date' => '2026-12-09', 'name' => 'Independence Day'],
            ['date' => '2026-12-25', 'name' => 'Christmas Day'],
            ['date' => '2026-12-26', 'name' => 'Boxing Day'],
        ];

        // Tanzania Public Holidays for 2027 (approximate - Islamic dates vary)
        $holidays2027 = [
            ['date' => '2027-01-01', 'name' => "New Year's Day"],
            ['date' => '2027-01-12', 'name' => 'Zanzibar Revolutionary Day'],
            ['date' => '2027-03-10', 'name' => 'Eid al-Fitr'],
            ['date' => '2027-03-11', 'name' => 'Eid al-Fitr (Holiday)'],
            ['date' => '2027-03-26', 'name' => 'Good Friday'],
            ['date' => '2027-03-29', 'name' => 'Easter Monday'],
            ['date' => '2027-04-07', 'name' => 'Karume Day'],
            ['date' => '2027-04-26', 'name' => 'Union Day'],
            ['date' => '2027-05-01', 'name' => 'Labour Day'],
            ['date' => '2027-05-17', 'name' => 'Eid al-Hajj'],
            ['date' => '2027-07-07', 'name' => 'Saba Saba'],
            ['date' => '2027-08-08', 'name' => 'Nane Nane'],
            ['date' => '2027-08-15', 'name' => 'Maulid Day'],
            ['date' => '2027-10-14', 'name' => 'Nyerere Day'],
            ['date' => '2027-12-09', 'name' => 'Independence Day'],
            ['date' => '2027-12-25', 'name' => 'Christmas Day'],
            ['date' => '2027-12-26', 'name' => 'Boxing Day'],
        ];

        foreach ($holidays2026 as $holiday) {
            ProjectHoliday::updateOrCreate(
                ['date' => $holiday['date']],
                array_merge($holiday, ['year' => 2026])
            );
        }

        foreach ($holidays2027 as $holiday) {
            ProjectHoliday::updateOrCreate(
                ['date' => $holiday['date']],
                array_merge($holiday, ['year' => 2027])
            );
        }
    }
}
