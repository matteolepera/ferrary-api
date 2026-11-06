<?php

use App\Models\CarModel;
use App\Models\Category;
use App\Models\Image;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Un modello visibile della famiglia, con una versione pubblicata e, se richiesta, una copertina */
function visibleModel(Category $category, string $publishedAt, ?string $coverPath): CarModel
{
    $model = CarModel::factory()->create(['category_id' => $category->id, 'published_at' => $publishedAt]);
    $version = Version::factory()->published()->create(['car_model_id' => $model->id]);

    if ($coverPath !== null) {
        Image::factory()->cover()->create(['version_id' => $version->id, 'path' => $coverPath]);
    }

    return $model;
}

test('ogni famiglia ha la copertina del suo modello pubblicato più di recente', function () {
    $category = Category::factory()->create();
    visibleModel($category, now()->subYear()->toDateTimeString(), 'versions/vecchio.jpg');
    visibleModel($category, now()->subDay()->toDateTimeString(), 'versions/recente.jpg');
    // Una bozza più recente non conta: il pubblico non la vede
    CarModel::factory()->create(['category_id' => $category->id, 'published_at' => null]);

    $url = $this->getJson('/api/categories')->assertOk()->json('data.0.cover.url');

    expect($url)->toEndWith('/storage/versions/recente.jpg');
});

test('una famiglia il cui modello non ha copertina non ha foto', function () {
    $category = Category::factory()->create();
    visibleModel($category, now()->subDay()->toDateTimeString(), null);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonPath('data.0.cover', null);
});
