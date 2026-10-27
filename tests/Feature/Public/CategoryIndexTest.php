<?php

use App\Models\CarModel;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('elenca solo le famiglie con modelli visibili, nell\'ordine dello staff', function () {
    $hypercar = Category::factory()->create(['slug' => 'hypercar', 'sort_order' => 2]);
    $v12 = Category::factory()->create(['slug' => 'berlinetta-v12', 'sort_order' => 1]);
    $empty = Category::factory()->create(['slug' => 'vuota', 'sort_order' => 0]);

    visibleCarModel(['category_id' => $v12->id]);
    visibleCarModel(['category_id' => $v12->id]);
    visibleCarModel(['category_id' => $hypercar->id]);
    // Un modello in bozza non rende visibile la sua famiglia
    CarModel::factory()->create(['category_id' => $empty->id, 'published_at' => null]);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'berlinetta-v12')
        ->assertJsonPath('data.0.models_count', 2)
        ->assertJsonPath('data.1.slug', 'hypercar')
        ->assertJsonPath('data.1.models_count', 1);
});

it('traduce il nome della famiglia', function () {
    $category = Category::factory()->create(['name' => ['it' => 'Berlinetta V12', 'en' => 'V12 Berlinetta']]);
    visibleCarModel(['category_id' => $category->id]);

    $this->getJson('/api/categories', ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJsonPath('data.0.name', 'V12 Berlinetta');
});
