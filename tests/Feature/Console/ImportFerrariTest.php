<?php

use App\Models\CarModel;
use App\Models\Version;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('importa modelli, versioni e collegamenti, e si può rilanciare senza doppioni', function () {
    $this->seed(CategorySeeder::class);

    $this->artisan('ferrary:import', ['--path' => 'tests/Fixtures/import'])->assertSuccessful();
    $this->artisan('ferrary:import', ['--path' => 'tests/Fixtures/import'])->assertSuccessful();

    $f40 = CarModel::where('slug', 'f40')->firstOrFail();
    $version = $f40->versions()->firstOrFail();

    expect(CarModel::count())->toBe(2)
        ->and(Version::count())->toBe(2)
        ->and($f40->category->slug)->toBe('supercar')
        ->and($f40->predecessor?->slug)->toBe('288-gto')
        ->and($f40->published_at)->not->toBeNull()
        ->and($version->powertrain?->engine?->code)->toBe('F120A')
        ->and($version->specs?->top_speed_kmh)->toEqual(324)
        ->and($version->designers()->wherePivot('role', 'carrozzeria')->count())->toBe(1);
});

test('si ferma su una categoria sconosciuta, senza salvare niente', function () {
    $this->seed(CategorySeeder::class);

    $this->artisan('ferrary:import', ['--path' => 'tests/Fixtures/import-errore'])
        ->expectsOutputToContain('Categoria sconosciuta "categoria-inventata"')
        ->assertFailed();

    expect(CarModel::count())->toBe(0);
});
