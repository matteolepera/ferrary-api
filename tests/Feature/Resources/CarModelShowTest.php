<?php

use App\Models\CarModel;
use App\Models\Image;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra il modello con le sole versioni pubblicate, in ordine', function () {
    $model = CarModel::factory()->published()->create(['slug' => '812']);

    $gts = Version::factory()->published()->create(['car_model_id' => $model->id, 'name' => '812 GTS', 'sort_order' => 2]);
    Version::factory()->published()->create(['car_model_id' => $model->id, 'name' => '812 Superfast', 'sort_order' => 1]);
    Version::factory()->create(['car_model_id' => $model->id, 'name' => '812 Bozza', 'sort_order' => 0]);

    Image::factory()->cover()->create(['version_id' => $gts->id]);

    $response = $this->getJson('/api/models/812')
        ->assertOk()
        ->assertJsonPath('data.slug', '812')
        ->assertJsonPath('data.category.slug', $model->category->slug);

    $versions = $response->json('data.versions');

    // La bozza non compare, le altre sono nell'ordine del selettore
    expect(array_column($versions, 'name'))->toBe(['812 Superfast', '812 GTS'])
        // Ogni versione porta con sé la chiave della copertina (null se non ce l'ha)
        ->and($versions[0])->toHaveKey('cover')
        ->and($versions[1]['cover'])->not->toBeNull();
});

it('risponde 404 per un modello in bozza', function () {
    CarModel::factory()
        ->has(Version::factory()->published(), 'versions')
        ->create(['slug' => 'bozza']);

    $this->getJson('/api/models/bozza')->assertNotFound();
});

it('risponde 404 per un modello senza versioni pubblicate', function () {
    CarModel::factory()->published()
        ->has(Version::factory(), 'versions')
        ->create(['slug' => 'vuoto']);

    $this->getJson('/api/models/vuoto')->assertNotFound();
});

it('risponde 404 per un modello nel cestino', function () {
    $model = CarModel::factory()->published()
        ->has(Version::factory()->published(), 'versions')
        ->create(['slug' => 'cestinato']);

    $model->delete();

    $this->getJson('/api/models/cestinato')->assertNotFound();
});

it('risponde 404 per uno slug inesistente', function () {
    $this->getJson('/api/models/non-esiste')->assertNotFound();
});
