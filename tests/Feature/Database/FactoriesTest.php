<?php

use App\Enums\PowertrainType;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Engine;
use App\Models\Image;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionRacing;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('crea record che rispettano i vincoli del database', function (string $model, ?string $state) {
    $factory = $model::factory();

    if ($state !== null) {
        $factory = $factory->{$state}();
    }

    expect($factory->create()->exists)->toBeTrue();
})->with([
    'categoria' => [Category::class, null],
    'modello' => [CarModel::class, null],
    'modello pubblicato' => [CarModel::class, 'published'],
    'versione' => [Version::class, null],
    'versione pubblicata' => [Version::class, 'published'],
    'versione in produzione' => [Version::class, 'inProduction'],
    'versione F1' => [Version::class, 'f1'],
    'versione one-off' => [Version::class, 'oneOff'],
    'specifiche' => [VersionSpec::class, null],
    'dati corsa' => [VersionRacing::class, null],
    'motore' => [Engine::class, null],
    'motore piatto' => [Engine::class, 'flat'],
    'designer studio' => [Designer::class, null],
    'designer persona' => [Designer::class, 'person'],
    'pilota' => [Driver::class, null],
    'immagine' => [Image::class, null],
    'copertina' => [Image::class, 'cover'],
    'utente master' => [User::class, 'master'],
]);

it('produce il tipo di motorizzazione atteso', function (string $state, PowertrainType $expected) {
    $powertrain = VersionPowertrain::factory()->{$state}()->create();

    expect($powertrain->fresh()->powertrain_type)->toBe($expected);
})->with([
    'termica' => ['thermal', PowertrainType::Termico],
    'ibrida' => ['hybrid', PowertrainType::Ibrido],
    'elettrica' => ['electric', PowertrainType::Elettrico],
]);
