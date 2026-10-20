<?php

use App\Models\Category;
use App\Models\Version;
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
