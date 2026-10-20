<?php

use App\Models\CarModel;
use App\Models\Image;
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

it('mostra la copertina della prima versione pubblicata', function () {
    $model = CarModel::factory()->published()->create();

    // Tre versioni con la propria copertina: vince la prima pubblicata nell'ordine
    $draft = Version::factory()->create(['car_model_id' => $model->id, 'sort_order' => 0]);
    $main = Version::factory()->published()->create(['car_model_id' => $model->id, 'sort_order' => 1]);
    $other = Version::factory()->published()->create(['car_model_id' => $model->id, 'sort_order' => 2]);

    Image::factory()->cover()->create(['version_id' => $draft->id, 'path' => 'versions/bozza.jpg']);
    Image::factory()->cover()->create(['version_id' => $main->id, 'path' => 'versions/principale.jpg']);
    Image::factory()->cover()->create(['version_id' => $other->id, 'path' => 'versions/altra.jpg']);

    $cover = $this->getJson('/api/models')->json('data.0.cover');

    // La bozza ha sort_order più basso, ma non è pubblicata: non deve essere scelta
    expect($cover['url'])->toEndWith('/storage/versions/principale.jpg');
});

it('restituisce una copertina vuota se la versione principale non ne ha', function () {
    CarModel::factory()->published()
        ->has(Version::factory()->published(), 'versions')
        ->create();

    $this->getJson('/api/models')
        ->assertOk()
        ->assertJsonPath('data.0.cover', null);
});
