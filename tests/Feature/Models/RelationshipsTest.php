<?php

use App\Enums\PowertrainType;
use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Engine;
use App\Models\VersionPowertrain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => ['it' => 'Berlinetta V12', 'en' => 'V12 berlinetta'],
        'slug' => 'berlinetta-v12',
    ]);

    $this->carModel = $this->category->carModels()->create([
        'name' => '812',
        'slug' => '812',
        'description' => ['it' => 'Berlinetta V12 a motore anteriore'],
    ]);

    $this->version = $this->carModel->versions()->create([
        'name' => '812 Superfast',
        'slug' => '812-superfast',
        'vehicle_type' => VehicleType::Stradale,
        'year_start' => 2017,
        'sort_order' => 1,
    ]);
});

it('collega categoria, modello e versioni in ordine', function () {
    $this->carModel->versions()->create([
        'name' => '812 GTS',
        'slug' => '812-gts',
        'vehicle_type' => VehicleType::Stradale,
        'year_start' => 2019,
        'sort_order' => 2,
    ]);

    expect($this->carModel->category->is($this->category))->toBeTrue()
        ->and($this->carModel->versions->pluck('name')->all())->toBe(['812 Superfast', '812 GTS']);
});

it('restituisce i testi nella lingua corrente', function () {
    expect($this->category->name)->toBe('Berlinetta V12');

    app()->setLocale('en');

    expect($this->category->name)->toBe('V12 berlinetta');
});

it('collega predecessore e successore', function () {
    $f12 = $this->category->carModels()->create([
        'name' => 'F12berlinetta',
        'slug' => 'f12berlinetta',
        'description' => ['it' => 'Berlinetta V12 del 2012'],
    ]);

    $this->carModel->update(['predecessor_id' => $f12->id]);

    expect($this->carModel->predecessor->is($f12))->toBeTrue()
        ->and($f12->successors->first()->is($this->carModel))->toBeTrue();
});

it('calcola il tipo di motorizzazione e collega il motore alle versioni', function () {
    $engine = Engine::create([
        'code' => 'F154FA',
        'architecture' => 'V',
        'cylinders' => 8,
        'displacement_cc' => 3990,
        'aspiration' => 'turbo',
    ]);

    $this->version->powertrain()->create([
        'engine_id' => $engine->id,
        'electric_power_cv' => 220,
    ]);

    expect(VersionPowertrain::find($this->version->id)->powertrain_type)->toBe(PowertrainType::Ibrido)
        ->and($engine->versions->first()->is($this->version))->toBeTrue();
});

it('collega i designer con il loro ruolo', function () {
    $designer = Designer::create([
        'name' => 'Centro Stile Ferrari',
        'slug' => 'centro-stile-ferrari',
        'type' => 'studio',
        'country' => 'IT',
    ]);

    $this->version->designers()->attach($designer, ['role' => 'design']);

    expect($this->version->designers->first()->pivot->role)->toBe('design');
});
