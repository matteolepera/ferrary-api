<?php

use App\Http\Resources\CarModelResource;
use App\Http\Resources\VersionResource;
use App\Models\CarModel;
use App\Models\Image;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('compone la scheda completa di una versione', function () {
    $version = Version::factory()->create();
    VersionSpec::factory()->create(['version_id' => $version->id]);
    VersionPowertrain::factory()->hybrid()->create(['version_id' => $version->id]);
    Image::factory()->cover()->create(['version_id' => $version->id]);

    // È quello che farà il controller della scheda: caricare tutte le relazioni in anticipo
    $version->load(['carModel', 'cover', 'images', 'specs', 'powertrain.engine', 'designers', 'drivers', 'racingRecords']);

    $data = toJsonArray(new VersionResource($version));

    expect($data['vehicle_type'])->toBe('stradale')
        ->and($data['model']['slug'])->toBe($version->carModel->slug)
        ->and($data['specs']['performance'])->toHaveKey('top_speed_kmh')
        ->and($data['powertrain']['type'])->toBe('ibrido')
        ->and($data['powertrain']['engine'])->toHaveKey('code')
        ->and($data['cover']['is_cover'])->toBeTrue()
        ->and($data['images'])->toHaveCount(1);
});

it('mostra solo i dati di base quando le relazioni non sono caricate', function () {
    $version = Version::factory()->create();

    $data = toJsonArray(new VersionResource($version));

    expect($data)->toHaveKeys(['id', 'name', 'slug', 'years', 'production'])
        ->and($data)->not->toHaveKeys(['model', 'specs', 'powertrain', 'images', 'published_at']);
});

it('compone il modello con categoria e versioni in ordine', function () {
    $model = CarModel::factory()->create();
    Version::factory()->create(['car_model_id' => $model->id, 'name' => '812 GTS', 'sort_order' => 2]);
    Version::factory()->create(['car_model_id' => $model->id, 'name' => '812 Superfast', 'sort_order' => 1]);

    $model->load(['category', 'versions']);

    $data = toJsonArray(new CarModelResource($model));

    expect($data['category']['slug'])->toBe($model->category->slug)
        ->and(array_column($data['versions'], 'name'))->toBe(['812 Superfast', '812 GTS'])
        ->and($data)->not->toHaveKey('published_at');
});

it('mostra il numero di versioni solo se è stato contato', function () {
    $model = CarModel::factory()->create();
    Version::factory()->count(3)->create(['car_model_id' => $model->id]);

    $counted = toJsonArray(new CarModelResource(CarModel::withCount('versions')->findOrFail($model->id)));
    $notCounted = toJsonArray(new CarModelResource($model));

    expect($counted['versions_count'])->toBe(3)
        ->and($notCounted)->not->toHaveKey('versions_count');
});
