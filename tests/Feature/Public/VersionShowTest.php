<?php

use App\Models\CarModel;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Image;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra la scheda completa della versione', function () {
    $model = visibleCarModel();
    $version = Version::factory()->published()->create([
        'car_model_id' => $model->id,
        'slug' => 'sf90-stradale',
    ]);

    VersionSpec::factory()->create(['version_id' => $version->id]);
    VersionPowertrain::factory()->hybrid()->create(['version_id' => $version->id]);
    Image::factory()->cover()->create(['version_id' => $version->id]);
    Image::factory()->create(['version_id' => $version->id]);
    $version->designers()->attach(Designer::factory()->create(), ['role' => 'design']);
    $version->drivers()->attach(Driver::factory()->create());

    $this->getJson('/api/versions/sf90-stradale')
        ->assertOk()
        ->assertJsonPath('data.model.slug', $model->slug)
        ->assertJsonPath('data.model.category.slug', $model->category->slug)
        ->assertJsonPath('data.powertrain.type', 'ibrido')
        ->assertJsonPath('data.cover.is_cover', true)
        ->assertJsonPath('data.designers.0.role', 'design')
        ->assertJsonCount(2, 'data.images')
        ->assertJsonCount(1, 'data.drivers')
        ->assertJsonStructure([
            'data' => [
                'specs' => ['dimensions', 'performance'],
                'powertrain' => ['engine' => ['code'], 'combustion', 'electric'],
            ],
        ]);
});

it('risponde 404 per una versione in bozza', function () {
    Version::factory()->create([
        'car_model_id' => visibleCarModel()->id,
        'slug' => 'bozza',
    ]);

    $this->getJson('/api/versions/bozza')->assertNotFound();
});

it('risponde 404 se il modello della versione non è visibile', function () {
    $draftModel = CarModel::factory()->create();
    Version::factory()->published()->create([
        'car_model_id' => $draftModel->id,
        'slug' => 'orfana',
    ]);

    $this->getJson('/api/versions/orfana')->assertNotFound();
});

it('risponde 404 per una versione nel cestino', function () {
    $version = Version::factory()->published()->create([
        'car_model_id' => visibleCarModel()->id,
        'slug' => 'cestinata',
    ]);

    $version->delete();

    $this->getJson('/api/versions/cestinata')->assertNotFound();
});

it('risponde 404 per uno slug inesistente', function () {
    $this->getJson('/api/versions/non-esiste')->assertNotFound();
});
