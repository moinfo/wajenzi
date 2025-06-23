<?php

namespace Database\Seeders;

use App\Models\AccessLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccessLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        foreach (range(1, 100) as $index => $item) {
            $now = now()->subMonths(100 - $index);
            AccessLog::query()->create([
                'ip_address' => $faker->ipv4,
                'user_agent' => $faker->userAgent,
                'url' => $faker->url,
                'method' => $faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

    }
}
