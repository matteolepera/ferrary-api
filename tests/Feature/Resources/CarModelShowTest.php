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

it('mostra predecessore e successori visibili', function () {
    $f12 = visibleCarModel(['name' => 'F12berlinetta', 'slug' => 'f12berlinetta']);
    $model = visibleCarModel(['slug' => '812', 'predecessor_id' => $f12->id]);
    visibleCarModel(['name' => '12Cilindri', 'slug' => '12cilindri', 'predecessor_id' => $model->id]);

    $this->getJson('/api/models/812')
        ->assertOk()
        ->assertJsonPath('data.predecessor.slug', 'f12berlinetta')
        ->assertJsonPath('data.successors.0.slug', '12cilindri');
});

it('nasconde predecessore e successori non visibili', function () {
    // Predecessore in bozza
    $draft = CarModel::factory()
        ->has(Version::factory()->published(), 'versions')
        ->create();

    $model = visibleCarModel(['slug' => '812', 'predecessor_id' => $draft->id]);

    // Successore pubblicato ma senza versioni
    CarModel::factory()->published()->create(['predecessor_id' => $model->id]);

    $this->getJson('/api/models/812')
        ->assertOk()
        ->assertJsonPath('data.predecessor', null)
        ->assertJsonCount(0, 'data.successors');
});
