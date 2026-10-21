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

it('crea una categoria con entrambe le traduzioni', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', [
            'name' => ['it' => 'Hypercar', 'en' => 'Hypercar'],
            'slug' => 'hypercar',
            'sort_order' => 3,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'hypercar')
        ->assertJsonPath('data.sort_order', 3);

    expect(Category::where('slug', 'hypercar')->exists())->toBeTrue();
});

it('genera lo slug dal nome italiano', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', ['name' => ['it' => 'Berlinetta V12']])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'berlinetta-v12');
});

it('salva solo le traduzioni compilate', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', ['name' => ['it' => 'Sport prototipi', 'en' => '']])
        ->assertCreated();

    expect(Category::firstOrFail()->getTranslations('name'))->toBe(['it' => 'Sport prototipi']);
});

it('richiede il nome italiano', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', ['name' => ['en' => 'Only English']])
        ->assertJsonValidationErrors('name.it');
});

it('rifiuta uno slug già usato, anche se generato', function () {
    Category::factory()->create(['slug' => 'berlinetta-v12']);

    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', ['name' => ['it' => 'Berlinetta V12']])
        ->assertJsonValidationErrors('slug');
});

it('rifiuta uno slug con un formato non valido', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson('/api/admin/categories', [
            'name' => ['it' => 'Hypercar'],
            'slug' => 'Hyper Car',
        ])
        ->assertJsonValidationErrors('slug');
});
