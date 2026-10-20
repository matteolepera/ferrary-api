<?php

use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Modello visibile la cui versione pubblicata inizia nell'anno indicato.
 */
function modelFromYear(string $name, int $year): CarModel
{
    $model = visibleCarModel(['name' => $name, 'slug' => strtolower($name)]);

    // year_end a null: l'anno di fine casuale della factory potrebbe essere prima del nuovo inizio
    $model->versions()->first()->update(['year_start' => $year, 'year_end' => null]);

    return $model;
}

/**
 * Gli slug dell'elenco, nell'ordine ricevuto.
 */
function sortedSlugs(string $sort): array
{
    return array_column(test()->getJson("/api/models?sort={$sort}")->json('data'), 'slug');
}

beforeEach(function () {
    modelFromYear('SF90', 2019);
    modelFromYear('F40', 1987);
    modelFromYear('812', 2017);
});

it('ordina per nome in ordine crescente e decrescente', function () {
    expect(sortedSlugs('name'))->toBe(['812', 'f40', 'sf90'])
        ->and(sortedSlugs('-name'))->toBe(['sf90', 'f40', '812']);
});

it('ordina per anno in ordine crescente e decrescente', function () {
    expect(sortedSlugs('year'))->toBe(['f40', '812', 'sf90'])
        ->and(sortedSlugs('-year'))->toBe(['sf90', '812', 'f40']);
});

it('usa l\'anno della prima versione pubblicata', function () {
    $model = CarModel::where('slug', '812')->firstOrFail();
    // Una bozza più vecchia non deve contare
    Version::factory()->create(['car_model_id' => $model->id, 'year_start' => 1950, 'year_end' => null]);

    $response = $this->getJson('/api/models?sort=year');

    expect(array_column($response->json('data'), 'slug'))->toBe(['f40', '812', 'sf90'])
        ->and($response->json('data.1.first_year'))->toBe(2017);
});

it('rifiuta un ordinamento non previsto', function () {
    $this->getJson('/api/models?sort=price')
        ->assertJsonValidationErrors('sort');
});
