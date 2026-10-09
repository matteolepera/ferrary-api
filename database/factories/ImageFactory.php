<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
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
            'path' => 'versions/'.fake()->uuid().'.jpg',
            'alt_text' => ['it' => fake()->sentence(), 'en' => fake()->sentence()],
            'is_cover' => false,
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(['is_cover' => true]);
    }
}
