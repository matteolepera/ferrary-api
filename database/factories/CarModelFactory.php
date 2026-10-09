<?php

namespace Database\Factories;

use App\Models\CarModel;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CarModel>
 */
class CarModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->bothify('F###');

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => ['it' => fake()->paragraph(), 'en' => fake()->paragraph()],
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()]);
    }
}
