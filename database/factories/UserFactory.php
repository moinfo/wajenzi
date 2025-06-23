<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'gender' => $this->faker->randomElement(['MALE','FEMALE']),
            'employee_number' => 'HRM/'.$this->faker->numberBetween(1,999),
            'national_id' => $this->faker->numberBetween(1970, 1998).$this->faker->numberBetween(10,12).$this->faker->numberBetween(10,30).'-'. $this->faker->numberBetween(111111111, 999999999),
            'tin' => $this->faker->numberBetween(111111111,999999999),
            'recruitment_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'department_id' => $this->faker->numberBetween(1, 5),
            'supervisor_id' => $this->faker->numberBetween(1, 5),
            'avatar_id' => 1,
            'email_verified_at' => now(),
            'password' => bcrypt('123456'), // password
            'remember_token' => Str::random(10),
        ];
    }
}
