<?php

use App\Http\Resources\CategoryResource;
use App\Http\Resources\DesignerResource;
use App\Http\Resources\DriverResource;
use App\Http\Resources\EngineResource;
use App\Http\Resources\ImageResource;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Engine;
use App\Models\Image;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restituisce il nome della categoria nella lingua corrente', function () {
    $category = Category::factory()->create([
        'name' => ['it' => 'Berlinetta V12', 'en' => 'V12 Berlinetta'],
    ]);

    app()->setLocale('en');

    expect((new CategoryResource($category))->resolve()['name'])->toBe('V12 Berlinetta');
});

it('restituisce le misure del motore come numeri', function () {
    $engine = Engine::factory()->create(['bore_mm' => 82.00]);

    // Senza il cast in Engine sarebbe la stringa "82.00"
    expect((new EngineResource($engine))->resolve()['bore_mm'])->toBe(82.0);
});

it('mostra il ruolo del designer solo quando arriva da una versione', function () {
    $designer = Designer::factory()->create();
    $version = Version::factory()->create();
    $version->designers()->attach($designer, ['role' => 'carrozzeria']);

    $fromVersion = (new DesignerResource($version->designers()->first()))->resolve();
    $standalone = (new DesignerResource($designer))->resolve();

    expect($fromVersion['role'])->toBe('carrozzeria')
        ->and($standalone)->not->toHaveKey('role');
});

it('restituisce la data di nascita del pilota in formato ISO', function () {
    $driver = Driver::factory()->create(['birth_date' => '1969-01-03']);

    expect((new DriverResource($driver))->resolve()['birth_date'])->toBe('1969-01-03');
});

it('restituisce l\'indirizzo completo dell\'immagine', function () {
    $image = Image::factory()->create(['path' => 'versions/f40.jpg']);

    expect((new ImageResource($image))->resolve()['url'])->toEndWith('/storage/versions/f40.jpg');
});
