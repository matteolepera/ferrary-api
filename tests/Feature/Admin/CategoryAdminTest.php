<?php

use App\Models\CarModel;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('elenca le categorie con tutte le traduzioni e il numero di modelli', function () {
    $second = Category::factory()->create(['sort_order' => 2]);
    $first = Category::factory()->create([
        'name' => ['it' => 'Berlinetta V12', 'en' => 'V12 Berlinetta'],
        'sort_order' => 1,
    ]);
    CarModel::factory()->count(2)->create(['category_id' => $first->id]);

    $this->actingAs(User::factory()->moderator()->create())
        ->getJson('/api/admin/categories')
        ->assertOk()
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.0.name.it', 'Berlinetta V12')
        ->assertJsonPath('data.0.name.en', 'V12 Berlinetta')
        ->assertJsonPath('data.0.car_models_count', 2)
        ->assertJsonPath('data.1.id', $second->id);
});

it('mostra una singola categoria', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->getJson("/api/admin/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.slug', $category->slug)
        ->assertJsonPath('data.car_models_count', 0);
});

it('richiede il login', function () {
    $this->getJson('/api/admin/categories')->assertUnauthorized();
});
