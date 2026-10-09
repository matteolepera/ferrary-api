<?php

namespace Database\Factories;

use App\Models\Version;
use App\Models\VersionSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionSpec>
 */
class VersionSpecFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $zeroTo100 = fake()->randomFloat(1, 2.5, 6.0);

        return [
            'version_id' => Version::factory(),
            'length_mm' => fake()->numberBetween(4200, 4800),
            'width_mm' => fake()->numberBetween(1900, 2100),
            'height_mm' => fake()->numberBetween(1100, 1300),
            'wheelbase_mm' => fake()->numberBetween(2500, 2750),
            'dry_weight_kg' => fake()->numberBetween(1200, 1700),
            'weight_front_pct' => fake()->numberBetween(40, 55),
            'chassis_material' => 'alluminio',
            'brakes' => 'carboceramici',
            'top_speed_kmh' => fake()->numberBetween(280, 350),
            'zero_to_100_sec' => $zeroTo100,
            'zero_to_200_sec' => $zeroTo100 + fake()->randomFloat(1, 4.0, 8.0),
            'fiorano_lap_sec' => fake()->randomFloat(2, 78, 90),
        ];
    }
}
