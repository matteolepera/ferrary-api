<?php

use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra solo i modelli pubblicati con almeno una versione pubblicata', function () {
    // has(..., 'versions') crea il modello insieme a una sua versione
    $visible = CarModel::factory()->published()
        ->has(Version::factory()->published(), 'versions')
        ->create();

    // Modello in bozza: escluso anche se la sua versione è pubblicata
    CarModel::factory()
        ->has(Version::factory()->published(), 'versions')
        ->create();

    // Modello pubblicato ma con la sola versione in bozza: escluso, sarebbe una scheda vuota
    CarModel::factory()->published()
        ->has(Version::factory(), 'versions')
        ->create();

    $this->getJson('/api/models')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $visible->slug);
});

it('include la categoria e conta solo le versioni pubblicate', function () {
    $model = CarModel::factory()->published()
        ->has(Version::factory()->published()->count(2), 'versions')
        ->has(Version::factory(), 'versions')
        ->create();

    $this->getJson('/api/models')
        ->assertOk()
        ->assertJsonPath('data.0.category.slug', $model->category->slug)
        ->assertJsonPath('data.0.versions_count', 2);
});

it('ordina i modelli per nome', function () {
    foreach (['SF90', '812', 'F40'] as $name) {
        CarModel::factory()->published()
            ->has(Version::factory()->published(), 'versions')
            ->create(['name' => $name, 'slug' => strtolower($name)]);
    }

    $names = array_column($this->getJson('/api/models')->json('data'), 'name');

    expect($names)->toBe(['812', 'F40', 'SF90']);
});

it('divide i risultati in pagine da 24', function () {
    CarModel::factory()->published()
        ->has(Version::factory()->published(), 'versions')
        ->count(30)
        ->create();

    $this->getJson('/api/models')
        ->assertJsonCount(24, 'data')
        ->assertJsonPath('meta.total', 30)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/models?page=2')
        ->assertJsonCount(6, 'data');
});
