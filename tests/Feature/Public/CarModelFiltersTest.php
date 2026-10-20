<?php

use App\Models\CarModel;
use App\Models\Category;
use App\Models\Version;
use App\Models\VersionPowertrain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filtra per categoria', function () {
    $hypercar = Category::factory()->create(['slug' => 'hypercar']);
    visibleCarModel(['slug' => 'f40', 'category_id' => $hypercar->id]);
    visibleCarModel(['slug' => '812']);

    $this->getJson('/api/models?category=hypercar')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'f40');
});

it('rifiuta una categoria inesistente', function () {
    $this->getJson('/api/models?category=non-esiste')
        ->assertJsonValidationErrors('category');
});

it('cerca nel nome del modello senza distinguere maiuscole e minuscole', function () {
    visibleCarModel(['name' => 'LaFerrari', 'slug' => 'laferrari']);
    visibleCarModel(['name' => 'F40', 'slug' => 'f40']);

    $this->getJson('/api/models?search=laferr')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'laferrari');
});

it('cerca anche nel nome delle versioni pubblicate', function () {
    $model = visibleCarModel(['name' => '812', 'slug' => '812']);
    Version::factory()->published()->create(['car_model_id' => $model->id, 'name' => '812 Superfast']);
    visibleCarModel(['name' => 'F40', 'slug' => 'f40']);

    $this->getJson('/api/models?search=superfast')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', '812');
});

it('ignora i nomi delle versioni in bozza', function () {
    $model = visibleCarModel(['name' => 'SF90', 'slug' => 'sf90']);
    Version::factory()->create(['car_model_id' => $model->id, 'name' => 'SF90 Segreta']);

    $this->getJson('/api/models?search=segreta')
        ->assertJsonCount(0, 'data');
});

it('mantiene i filtri nei link della paginazione', function () {
    Category::factory()->create(['slug' => 'hypercar']);

    $this->getJson('/api/models?category=hypercar')
        ->assertJsonPath('meta.links.1.url', fn (?string $url) => str_contains((string) $url, 'category=hypercar'));
});

it('filtra per tipo di veicolo', function () {
    $f1 = visibleCarModel(['slug' => 'f2004']);
    Version::factory()->published()->f1()->create(['car_model_id' => $f1->id]);
    visibleCarModel(['slug' => 'f40']);

    $this->getJson('/api/models?vehicle_type=f1')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'f2004');
});

it('filtra per motorizzazione', function () {
    $hybrid = visibleCarModel(['slug' => 'sf90']);
    VersionPowertrain::factory()->hybrid()->create(['version_id' => $hybrid->versions()->first()->id]);
    $thermal = visibleCarModel(['slug' => '812']);
    VersionPowertrain::factory()->create(['version_id' => $thermal->versions()->first()->id]);

    $this->getJson('/api/models?powertrain=ibrido')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'sf90');
});

it('filtra per periodo di produzione', function () {
    $f40 = visibleCarModel(['slug' => 'f40']);
    $f40->versions()->first()->update(['year_start' => 1987, 'year_end' => 1992]);
    $current = visibleCarModel(['slug' => 'attuale']);
    $current->versions()->first()->update(['year_start' => 2020, 'year_end' => null, 'in_production' => true]);

    $slugs = fn (string $query) => array_column($this->getJson("/api/models?{$query}")->json('data'), 'slug');

    expect($slugs('year_from=2000'))->toBe(['attuale'])
        ->and($slugs('year_to=1995'))->toBe(['f40'])
        // Il test riguarda il filtro, non l'ordine: confronto indipendente dall'ordine
        ->and($slugs('year_from=1990&year_to=2025'))->toEqualCanonicalizing(['attuale', 'f40']);
});

it('applica tutti i filtri sulle versioni alla stessa versione', function () {
    $model = CarModel::factory()->published()->create(['slug' => 'misto']);
    // Una versione F1 termica e una stradale ibrida: nessuna è una F1 ibrida
    $f1 = Version::factory()->published()->f1()->create(['car_model_id' => $model->id]);
    VersionPowertrain::factory()->create(['version_id' => $f1->id]);
    $road = Version::factory()->published()->create(['car_model_id' => $model->id]);
    VersionPowertrain::factory()->hybrid()->create(['version_id' => $road->id]);

    $this->getJson('/api/models?vehicle_type=f1&powertrain=ibrido')
        ->assertJsonCount(0, 'data');
});

it('valida i filtri sulle versioni', function () {
    $this->getJson('/api/models?vehicle_type=astronave')
        ->assertJsonValidationErrors('vehicle_type');

    $this->getJson('/api/models?year_from=2000&year_to=1990')
        ->assertJsonValidationErrors('year_to');
});
