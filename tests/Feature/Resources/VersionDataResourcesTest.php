<?php

use App\Http\Resources\VersionPowertrainResource;
use App\Http\Resources\VersionRacingResource;
use App\Http\Resources\VersionSpecResource;
use App\Models\VersionPowertrain;
use App\Models\VersionRacing;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('raggruppa le specifiche e restituisce numeri', function () {
    $spec = VersionSpec::factory()->create(['length_mm' => 4358, 'zero_to_100_sec' => 2.9]);

    $data = toJsonArray(new VersionSpecResource($spec));

    expect($data['dimensions']['length_mm'])->toBe(4358)
        ->and($data['performance']['zero_to_100_sec'])->toBe(2.9);
});

it('mostra sia la parte termica sia quella elettrica per un\'ibrida', function () {
    $created = VersionPowertrain::factory()->hybrid()->create();

    // Rilegge dal database, così include il tipo calcolato da MySQL, e carica il motore
    $powertrain = VersionPowertrain::with('engine')->findOrFail($created->version_id);

    $data = toJsonArray(new VersionPowertrainResource($powertrain));

    expect($data['type'])->toBe('ibrido')
        ->and($data['combustion'])->not->toBeNull()
        ->and($data['electric'])->not->toBeNull()
        ->and($data['engine']['code'])->toBe($powertrain->engine->code);
});

it('mostra solo la parte elettrica per un\'elettrica', function () {
    $created = VersionPowertrain::factory()->electric()->create();
    $powertrain = VersionPowertrain::with('engine')->findOrFail($created->version_id);

    $data = toJsonArray(new VersionPowertrainResource($powertrain));

    expect($data['type'])->toBe('elettrico')
        ->and($data['combustion'])->toBeNull()
        ->and($data['engine'])->toBeNull()
        ->and($data['electric']['power_cv'])->toBeInt();
});

it('omette il motore se la relazione non è stata caricata', function () {
    $powertrain = VersionPowertrain::factory()->create();

    $data = toJsonArray(new VersionPowertrainResource($powertrain));

    expect($data)->not->toHaveKey('engine');
});

it('restituisce le statistiche sportive', function () {
    // Valori coerenti tra loro: vittorie ≤ gare e vittorie ≤ podi.
    // Sovrascrivere solo 'wins' violerebbe i vincoli, con i valori casuali della factory.
    $racing = VersionRacing::factory()->create([
        'championship' => 'Formula 1',
        'races' => 18,
        'wins' => 15,
        'podiums' => 29,
    ]);

    $data = toJsonArray(new VersionRacingResource($racing));

    expect($data['championship'])->toBe('Formula 1')
        ->and($data['wins'])->toBe(15);
});
