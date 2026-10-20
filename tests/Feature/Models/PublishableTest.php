<?php

use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('considera pubblicati solo i record con una data già passata', function (string $model) {
    $draft = $model::factory()->create(['published_at' => null]);
    $published = $model::factory()->create(['published_at' => now()->subDay()]);
    $scheduled = $model::factory()->create(['published_at' => now()->addDay()]);

    expect($model::published()->pluck('id')->all())->toBe([$published->id]);
})->with([
    'modelli' => CarModel::class,
    'versioni' => Version::class,
]);

it('riconosce lo stato di pubblicazione di un singolo record', function () {
    $draft = CarModel::factory()->create(['published_at' => null]);
    $published = CarModel::factory()->create(['published_at' => now()->subDay()]);
    $scheduled = CarModel::factory()->create(['published_at' => now()->addDay()]);

    expect($draft->isPublished())->toBeFalse()
        ->and($published->isPublished())->toBeTrue()
        ->and($scheduled->isPublished())->toBeFalse();
});
