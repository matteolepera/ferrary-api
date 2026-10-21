<?php

use App\Models\CarModel;
use App\Models\Driver;
use App\Models\Version;
use App\Models\VersionRacing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('elenca solo i piloti con versioni visibili, ordinati per cognome', function () {
    $visible = Version::factory()->published()->create(['car_model_id' => visibleCarModel()->id]);

    Driver::factory()->create(['last_name' => 'Schumacher', 'slug' => 'michael-schumacher'])
        ->versions()->attach($visible);
    Driver::factory()->create(['last_name' => 'Barrichello', 'slug' => 'rubens-barrichello'])
        ->versions()->attach($visible);
    Driver::factory()->create(['slug' => 'senza-auto'])
        ->versions()->attach(Version::factory()->create());

    $slugs = array_column($this->getJson('/api/drivers')->assertOk()->json('data'), 'slug');

    expect($slugs)->toBe(['rubens-barrichello', 'michael-schumacher']);
});

it('mostra un pilota con le versioni visibili e le loro statistiche', function () {
    $driver = Driver::factory()->create(['slug' => 'michael-schumacher']);

    $visible = Version::factory()->published()->f1()->create(['car_model_id' => visibleCarModel()->id]);
    VersionRacing::factory()->create([
        'version_id' => $visible->id,
        'races' => 18,
        'wins' => 15,
        'podiums' => 29,
        'poles' => 12,
    ]);
    $hidden = Version::factory()->published()->create(['car_model_id' => CarModel::factory()->create()->id]);

    $driver->versions()->attach([$visible->id, $hidden->id]);

    $this->getJson('/api/drivers/michael-schumacher')
        ->assertOk()
        ->assertJsonCount(1, 'data.versions')
        ->assertJsonPath('data.versions.0.slug', $visible->slug)
        ->assertJsonPath('data.versions.0.racing.0.wins', 15);
});

it('risponde 404 per un pilota senza versioni visibili', function () {
    Driver::factory()->create(['slug' => 'senza-auto']);

    $this->getJson('/api/drivers/senza-auto')->assertNotFound();
});
