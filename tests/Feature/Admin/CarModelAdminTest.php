<?php

use App\Models\CarModel;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
});

it('elenca tutti i modelli, comprese bozze e programmati, con il loro stato', function () {
    CarModel::factory()->create(['published_at' => null]);
    CarModel::factory()->create(['published_at' => now()->addDay()]);
    CarModel::factory()->create(['published_at' => now()->subDay()]);

    $statuses = collect($this->getJson('/api/admin/models')->assertOk()->json('data'))
        ->pluck('status')
        ->sort()
        ->values()
        ->all();

    expect($statuses)->toBe(['draft', 'published', 'scheduled']);
});

it('filtra per stato', function () {
    CarModel::factory()->create(['slug' => 'bozza', 'published_at' => null]);
    CarModel::factory()->create(['slug' => 'pubblicata', 'published_at' => now()->subDay()]);

    $this->getJson('/api/admin/models?status=draft')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'bozza');
});

it('filtra per nome e categoria', function () {
    $hypercar = Category::factory()->create();
    CarModel::factory()->create(['name' => 'F40', 'slug' => 'f40', 'category_id' => $hypercar->id]);
    CarModel::factory()->create(['name' => 'F50', 'slug' => 'f50', 'category_id' => $hypercar->id]);
    CarModel::factory()->create(['name' => 'F40 Competizione', 'slug' => 'f40-competizione']);

    $this->getJson("/api/admin/models?search=F40&category_id={$hypercar->id}")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'f40');
});

it('mostra prima i modelli modificati di recente', function () {
    CarModel::factory()->create(['slug' => 'vecchio', 'updated_at' => now()->subWeek()]);
    CarModel::factory()->create(['slug' => 'recente', 'updated_at' => now()->subHour()]);

    $this->getJson('/api/admin/models')
        ->assertJsonPath('data.0.slug', 'recente');
});

it('mostra un modello con traduzioni, categoria e predecessore', function () {
    $predecessor = CarModel::factory()->create();
    $model = CarModel::factory()->create([
        'description' => ['it' => 'Descrizione', 'en' => 'Description'],
        'predecessor_id' => $predecessor->id,
    ]);

    $this->getJson("/api/admin/models/{$model->id}")
        ->assertOk()
        ->assertJsonPath('data.description.en', 'Description')
        ->assertJsonPath('data.category.id', $model->category_id)
        ->assertJsonPath('data.predecessor.id', $predecessor->id)
        ->assertJsonPath('data.versions_count', 0);
});

it('richiede il login', function () {
    auth()->logout();

    $this->getJson('/api/admin/models')->assertUnauthorized();
});
