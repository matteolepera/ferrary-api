<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createCarModel(array $overrides = []): int
{
    $categoryId = DB::table('categories')->insertGetId([
        'name' => json_encode(['it' => 'Berlinetta V12']),
        'slug' => 'berlinetta-v12',
    ]);

    return DB::table('car_models')->insertGetId(array_merge([
        'category_id' => $categoryId,
        'name' => '812',
        'slug' => '812',
        'description' => json_encode(['it' => 'Berlinetta V12 a motore anteriore']),
    ], $overrides));
}

function createVersion(array $overrides = []): int
{
    return DB::table('versions')->insertGetId(array_merge([
        'car_model_id' => createCarModel(),
        'name' => '812 Superfast',
        'slug' => '812-superfast',
        'vehicle_type' => 'stradale',
        'year_start' => 2017,
    ], $overrides));
}

it('accetta dati coerenti', function () {
    createVersion(['year_end' => 2023]);

    expect(DB::table('versions')->count())->toBe(1);
});

it('rifiuta un modello senza descrizione in italiano', function () {
    expect(fn () => createCarModel(['description' => json_encode(['en' => 'Front-engine V12'])]))
        ->toThrow(QueryException::class, 'chk_car_models_description_it');
});

it('rifiuta un anno di fine precedente a quello di inizio', function () {
    expect(fn () => createVersion(['year_start' => 2017, 'year_end' => 2015]))
        ->toThrow(QueryException::class, 'chk_versions_years');
});

it('rifiuta una versione in produzione con un anno di fine', function () {
    expect(fn () => createVersion(['in_production' => true, 'year_end' => 2023]))
        ->toThrow(QueryException::class, 'chk_versions_in_production');
});

it('rifiuta una motorizzazione senza alcun motore', function () {
    $versionId = createVersion();

    expect(fn () => DB::table('version_powertrains')->insert(['version_id' => $versionId]))
        ->toThrow(QueryException::class, 'chk_pt_has_propulsion');
});

it('rifiuta una batteria senza parte elettrica', function () {
    $versionId = createVersion();
    $engineId = DB::table('engines')->insertGetId([
        'code' => 'F140GA',
        'architecture' => 'V',
        'cylinders' => 12,
        'displacement_cc' => 6496,
        'aspiration' => 'aspirato',
    ]);

    expect(fn () => DB::table('version_powertrains')->insert([
        'version_id' => $versionId,
        'engine_id' => $engineId,
        'battery_kwh' => 7.9,
    ]))->toThrow(QueryException::class, 'chk_pt_electric_data');
});

it('rifiuta più vittorie che podi', function () {
    $versionId = createVersion(['vehicle_type' => 'f1', 'body_type' => 'monoposto']);

    expect(fn () => DB::table('version_racing')->insert([
        'version_id' => $versionId,
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'wins' => 15,
        'podiums' => 10,
    ]))->toThrow(QueryException::class, 'chk_racing_wins_podiums');
});

it('accetta più podi che gare per le statistiche di squadra', function () {
    $versionId = createVersion(['vehicle_type' => 'f1', 'body_type' => 'monoposto']);

    DB::table('version_racing')->insert([
        'version_id' => $versionId,
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'races' => 18,
        'wins' => 15,
        'podiums' => 29,
    ]);

    expect(DB::table('version_racing')->count())->toBe(1);
});

it('rifiuta più vittorie che gare', function () {
    $versionId = createVersion(['vehicle_type' => 'f1', 'body_type' => 'monoposto']);

    expect(fn () => DB::table('version_racing')->insert([
        'version_id' => $versionId,
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'races' => 18,
        'wins' => 20,
        'podiums' => 30,
    ]))->toThrow(QueryException::class, 'chk_racing_wins_races');
});
