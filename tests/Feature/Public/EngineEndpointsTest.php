<?php

use App\Models\CarModel;
use App\Models\Engine;
use App\Models\Version;
use App\Models\VersionPowertrain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crea una versione con il motore indicato, nel modello indicato.
 */
function versionWithEngine(Engine $engine, CarModel $model, bool $published = true): Version
{
    $factory = Version::factory();
    $version = ($published ? $factory->published() : $factory)->create(['car_model_id' => $model->id]);

    VersionPowertrain::factory()->create(['version_id' => $version->id, 'engine_id' => $engine->id]);

    return $version;
}

it('elenca solo i motori con versioni visibili', function () {
    $used = Engine::factory()->create(['code' => 'F140GA']);
    versionWithEngine($used, visibleCarModel());
    versionWithEngine($used, visibleCarModel());

    $unused = Engine::factory()->create(['code' => 'F999XX']);
    versionWithEngine($unused, visibleCarModel(), published: false);

    $this->getJson('/api/engines')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'F140GA')
        ->assertJsonPath('data.0.versions_count', 2);
});

it('mostra un motore con le sole versioni visibili', function () {
    $engine = Engine::factory()->create();

    $visible = versionWithEngine($engine, visibleCarModel());
    // Versione in bozza
    versionWithEngine($engine, visibleCarModel(), published: false);
    // Versione pubblicata, ma di un modello in bozza
    versionWithEngine($engine, CarModel::factory()->create());

    $versions = $this->getJson("/api/engines/{$engine->id}")
        ->assertOk()
        ->json('data.versions');

    expect($versions)->toHaveCount(1)
        ->and($versions[0]['slug'])->toBe($visible->slug)
        ->and($versions[0])->toHaveKeys(['model', 'cover']);
});

it('risponde 404 per un motore senza versioni visibili', function () {
    $engine = Engine::factory()->create();

    $this->getJson("/api/engines/{$engine->id}")->assertNotFound();
});
