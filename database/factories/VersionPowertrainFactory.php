<?php

namespace Database\Factories;

use App\Models\Engine;
use App\Models\Version;
use App\Models\VersionPowertrain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionPowertrain>
 */
class VersionPowertrainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version_id' => Version::factory(),
            'engine_id' => Engine::factory(),
            'ice_power_cv' => fake()->numberBetween(400, 830),
            'ice_torque_nm' => fake()->numberBetween(450, 800),
            'ice_max_rpm' => fake()->numberBetween(7000, 9500),
            'gearbox_type' => 'doppia_frizione',
            'gears' => fake()->randomElement([7, 8]),
            'drivetrain' => 'posteriore',
            'fuel_consumption_l100' => fake()->randomFloat(1, 10, 18),
        ];
    }

    public function thermal(): static
    {
        return $this->state([]);
    }

    public function hybrid(): static
    {
        return $this->state([
            'electric_motors_count' => fake()->numberBetween(1, 3),
            'electric_power_cv' => fake()->numberBetween(150, 300),
            'battery_kwh' => fake()->randomFloat(2, 5, 10),
            'electric_range_km' => fake()->numberBetween(20, 30),
            'system_power_cv' => fake()->numberBetween(800, 1050),
            'drivetrain' => 'integrale',
        ]);
    }

    public function electric(): static
    {
        return $this->state([
            'engine_id' => null,
            'ice_power_cv' => null,
            'ice_torque_nm' => null,
            'ice_max_rpm' => null,
            'fuel_consumption_l100' => null,
            'electric_motors_count' => 4,
            'electric_power_cv' => fake()->numberBetween(800, 1100),
            'battery_kwh' => fake()->randomFloat(2, 90, 120),
            'electric_range_km' => fake()->numberBetween(400, 550),
            'system_power_cv' => null,
            'gearbox_type' => null,
            'gears' => null,
            'drivetrain' => 'integrale',
        ]);
    }
}
