<?php

use App\Models\Designer;
use App\Models\Driver;
use App\Models\Image;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crea una versione completa con $items immagini, designer e piloti.
 */
function versionWithItems(string $slug, int $items): Version
{
    $version = Version::factory()->published()->create([
        'car_model_id' => visibleCarModel()->id,
        'slug' => $slug,
    ]);

    VersionSpec::factory()->create(['version_id' => $version->id]);
    VersionPowertrain::factory()->create(['version_id' => $version->id]);
    Image::factory()->count($items)->create(['version_id' => $version->id]);
    $version->designers()->attach(Designer::factory()->count($items)->create(), ['role' => 'design']);
    $version->drivers()->attach(Driver::factory()->count($items)->create());

    return $version;
}

it('carica la scheda della versione con un numero fisso di query', function () {
    versionWithItems('piccola', 1);
    versionWithItems('grande', 10);

    $small = countQueries(fn () => $this->getJson('/api/versions/piccola')->assertOk());
    $large = countQueries(fn () => $this->getJson('/api/versions/grande')->assertOk());

    // Con il problema N+1 la scheda "grande" farebbe molte più query
    expect($large)->toBe($small);
});

it('carica l\'elenco dei modelli con un numero fisso di query', function () {
    visibleCarModel();
    $few = countQueries(fn () => $this->getJson('/api/models')->assertOk());

    foreach (range(1, 9) as $i) {
        visibleCarModel();
    }
    $many = countQueries(fn () => $this->getJson('/api/models')->assertOk());

    expect($many)->toBe($few);
});
