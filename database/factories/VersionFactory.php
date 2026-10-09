<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Version>
 */
class VersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Versione ??##');
        $yearStart = fake()->numberBetween(1947, 2020);

        return [
            'car_model_id' => CarModel::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'vehicle_type' => VehicleType::Stradale,
            'body_type' => 'coupe',
            'year_start' => $yearStart,
            'year_end' => $yearStart + fake()->numberBetween(1, 6),
            'in_production' => false,
            'production_type' => 'serie',
            'units_produced' => fake()->numberBetween(100, 10000),
            'description' => ['it' => fake()->paragraph()],
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()]);
    }

    public function inProduction(): static
    {
        return $this->state([
            'year_start' => fake()->numberBetween(2020, 2025),
            'year_end' => null,
            'in_production' => true,
            'units_produced' => null,
        ]);
    }

    public function f1(): static
    {
        return $this->state([
            'vehicle_type' => VehicleType::F1,
            'body_type' => 'monoposto',
            'production_type' => null,
            'units_produced' => null,
        ]);
    }

    public function oneOff(): static
    {
        return $this->state([
            'production_type' => 'one_off',
            'units_produced' => 1,
        ]);
    }
}
