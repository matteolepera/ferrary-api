<?php

namespace Database\Factories;

use App\Models\Designer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Designer>
 */
class DesignerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'studio',
            'country' => 'IT',
        ];
    }

    public function person(): static
    {
        $name = fake()->unique()->name();

        return $this->state([
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'persona',
        ]);
    }
}
