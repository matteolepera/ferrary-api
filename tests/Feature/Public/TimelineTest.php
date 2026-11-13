<?php

use App\Models\CarModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Tre Ferrari visibili di anni diversi, date in disordine
    foreach ([['SF90', 2019], ['F40', 1987], ['166 Inter', 1950]] as [$name, $year]) {
        $model = visibleCarModel(['name' => $name, 'slug' => Str::slug($name)]);
        // year_end a null: la fine casuale della factory potrebbe essere prima del nuovo inizio
        $model->versions()->first()->update(['year_start' => $year, 'year_end' => null]);
    }
});

it('elenca tutte le Ferrari visibili in ordine di anno, senza paginazione', function () {
    $response = $this->getJson('/api/models/timeline')->assertOk();

    expect(array_column($response->json('data'), 'slug'))->toBe(['166-inter', 'f40', 'sf90'])
        ->and($response->json('data.0.first_year'))->toBe(1950)
        ->and($response->json('meta'))->toBeNull();
});

it('per ogni Ferrari manda solo i campi della linea del tempo', function () {
    $first = $this->getJson('/api/models/timeline')->json('data.0');

    expect(array_keys($first))->toBe(['id', 'slug', 'name', 'first_year', 'category'])
        ->and(array_keys($first['category']))->toBe(['slug', 'name']);
});

it('non mostra le bozze', function () {
    CarModel::factory()->create(['published_at' => null]);

    expect($this->getJson('/api/models/timeline')->json('data'))->toHaveCount(3);
});
