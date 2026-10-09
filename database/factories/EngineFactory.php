<?php

namespace Database\Factories;

use App\Models\Engine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Engine>
 */
class EngineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('F###??')),
            'architecture' => 'V',
            'v_angle_deg' => 90,
            'cylinders' => fake()->randomElement([8, 12]),
            'displacement_cc' => fake()->numberBetween(2900, 6500),
            'bore_mm' => fake()->randomFloat(2, 80, 95),
            'stroke_mm' => fake()->randomFloat(2, 70, 85),
            'valves_per_cylinder' => 4,
            'aspiration' => fake()->randomElement(['aspirato', 'turbo']),
        ];
    }

    public function flat(): static
    {
        return $this->state([
            'architecture' => 'piatto',
            'v_angle_deg' => null,
            'cylinders' => 12,
        ]);
    }
}
