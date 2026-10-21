<?php

use App\Models\CarModel;
use App\Models\Designer;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('elenca solo i designer con versioni visibili', function () {
    $active = Designer::factory()->create(['slug' => 'pininfarina']);
    $active->versions()->attach(
        Version::factory()->published()->count(2)->create(['car_model_id' => visibleCarModel()->id]),
        ['role' => 'design'],
    );

    $inactive = Designer::factory()->create();
    $inactive->versions()->attach(Version::factory()->create(), ['role' => 'design']);

    $this->getJson('/api/designers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'pininfarina')
        ->assertJsonPath('data.0.versions_count', 2);
});

it('mostra un designer con le versioni visibili e il suo ruolo', function () {
    $designer = Designer::factory()->create(['slug' => 'scaglietti']);

    $visible = Version::factory()->published()->create(['car_model_id' => visibleCarModel()->id]);
    $hidden = Version::factory()->published()->create(['car_model_id' => CarModel::factory()->create()->id]);

    $designer->versions()->attach($visible, ['role' => 'carrozzeria']);
    $designer->versions()->attach($hidden, ['role' => 'carrozzeria']);

    $this->getJson('/api/designers/scaglietti')
        ->assertOk()
        ->assertJsonCount(1, 'data.versions')
        ->assertJsonPath('data.versions.0.slug', $visible->slug)
        ->assertJsonPath('data.versions.0.designer_role', 'carrozzeria');
});

it('risponde 404 per un designer senza versioni visibili', function () {
    Designer::factory()->create(['slug' => 'senza-auto']);

    $this->getJson('/api/designers/senza-auto')->assertNotFound();
});
