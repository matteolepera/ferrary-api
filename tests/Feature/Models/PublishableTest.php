<?php

use App\Enums\PublicationStatus;
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

it('calcola lo stato di pubblicazione', function () {
    $draft = CarModel::factory()->create(['published_at' => null]);
    $scheduled = CarModel::factory()->create(['published_at' => now()->addDay()]);
    $published = CarModel::factory()->create(['published_at' => now()->subDay()]);

    expect($draft->publicationStatus())->toBe(PublicationStatus::Draft)
        ->and($scheduled->publicationStatus())->toBe(PublicationStatus::Scheduled)
        ->and($published->publicationStatus())->toBe(PublicationStatus::Published);
});

it('filtra per stato di pubblicazione', function (PublicationStatus $status, string $expected) {
    CarModel::factory()->create(['slug' => 'bozza', 'published_at' => null]);
    CarModel::factory()->create(['slug' => 'programmata', 'published_at' => now()->addDay()]);
    CarModel::factory()->create(['slug' => 'pubblicata', 'published_at' => now()->subDay()]);

    expect(CarModel::status($status)->pluck('slug')->all())->toBe([$expected]);
})->with([
    'bozze' => [PublicationStatus::Draft, 'bozza'],
    'programmate' => [PublicationStatus::Scheduled, 'programmata'],
    'pubblicate' => [PublicationStatus::Published, 'pubblicata'],
]);
