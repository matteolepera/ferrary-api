<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'slug' => Str::slug("{$firstName} {$lastName}").'-'.fake()->unique()->numberBetween(1, 99999),
            'nationality' => fake()->countryCode(),
            'birth_date' => fake()->date('Y-m-d', '2000-01-01'),
        ];
    }
}
