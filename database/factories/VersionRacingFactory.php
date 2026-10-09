<?php

namespace Database\Factories;

use App\Models\Version;
use App\Models\VersionRacing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VersionRacing>
 */
class VersionRacingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $races = fake()->numberBetween(10, 24);
        $podiums = fake()->numberBetween(0, $races);

        return [
            'version_id' => Version::factory()->f1(),
            'championship' => 'Formula 1',
            'season_start' => fake()->numberBetween(1950, 2025),
            'season_end' => null,
            'races' => $races,
            'wins' => fake()->numberBetween(0, $podiums),
            'poles' => fake()->numberBetween(0, $races),
            'podiums' => $podiums,
            'fastest_laps' => fake()->numberBetween(0, $races),
            'drivers_titles' => 0,
            'constructors_titles' => 0,
        ];
    }
}
