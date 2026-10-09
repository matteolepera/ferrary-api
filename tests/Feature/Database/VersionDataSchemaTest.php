<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('crea le tabelle dei dati delle versioni', function () {
    expect(Schema::hasColumns('version_specs', ['version_id', 'top_speed_kmh', 'zero_to_100_sec']))->toBeTrue()
        ->and(Schema::hasColumns('version_powertrains', ['version_id', 'engine_id', 'powertrain_type']))->toBeTrue()
        ->and(Schema::hasColumns('version_racing', ['version_id', 'championship', 'wins']))->toBeTrue()
        ->and(Schema::hasColumns('images', ['version_id', 'path', 'is_cover']))->toBeTrue();
});

it('calcola il tipo di motorizzazione dai dati', function () {
    $categoryId = DB::table('categories')->insertGetId([
        'name' => json_encode(['it' => 'Supercar']),
        'slug' => 'supercar',
    ]);
    $modelId = DB::table('car_models')->insertGetId([
        'category_id' => $categoryId,
        'name' => 'SF90',
        'slug' => 'sf90',
        'description' => json_encode(['it' => 'Prima Ferrari ibrida plug-in di serie']),
    ]);
    $versionId = DB::table('versions')->insertGetId([
        'car_model_id' => $modelId,
        'name' => 'SF90 Stradale',
        'slug' => 'sf90-stradale',
        'vehicle_type' => 'stradale',
        'year_start' => 2019,
    ]);
    $engineId = DB::table('engines')->insertGetId([
        'code' => 'F154FA',
        'architecture' => 'V',
        'cylinders' => 8,
        'displacement_cc' => 3990,
        'aspiration' => 'turbo',
    ]);

    DB::table('version_powertrains')->insert([
        'version_id' => $versionId,
        'engine_id' => $engineId,
        'electric_power_cv' => 220,
    ]);

    expect(DB::table('version_powertrains')->value('powertrain_type'))->toBe('ibrido');
});
