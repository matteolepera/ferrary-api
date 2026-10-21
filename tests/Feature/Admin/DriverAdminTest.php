<?php

use App\Models\Driver;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
});

it('elenca i piloti con il numero di versioni', function () {
    $driver = Driver::factory()->create();
    $driver->versions()->attach(Version::factory()->count(2)->create());

    $this->getJson('/api/admin/drivers')
        ->assertOk()
        ->assertJsonPath('data.0.versions_count', 2);
});

it('crea un pilota con lo slug generato da nome e cognome', function () {
    $this->postJson('/api/admin/drivers', [
        'first_name' => 'Michael',
        'last_name' => 'Schumacher',
        'nationality' => 'DE',
        'birth_date' => '1969-01-03',
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'michael-schumacher')
        ->assertJsonPath('data.birth_date', '1969-01-03');
});

it('rifiuta una data di nascita futura e una nazionalità non valida', function () {
    $this->postJson('/api/admin/drivers', [
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'nationality' => 'ITA',
        'birth_date' => now()->addYear()->toDateString(),
    ])
        ->assertJsonValidationErrors(['nationality', 'birth_date']);
});

it('modifica un pilota', function () {
    $driver = Driver::factory()->create();

    $this->patchJson("/api/admin/drivers/{$driver->id}", [
        'first_name' => 'Rubens',
        'last_name' => 'Barrichello',
        'slug' => 'rubens-barrichello',
        'nationality' => 'BR',
        'birth_date' => '1972-05-23',
    ])
        ->assertOk()
        ->assertJsonPath('data.last_name', 'Barrichello');
});

it('permette all\'admin di eliminare un pilota non collegato', function () {
    $driver = Driver::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/drivers/{$driver->id}")
        ->assertNoContent();

    $this->assertModelMissing($driver);
});

it('non elimina un pilota collegato a una versione', function () {
    $driver = Driver::factory()->create();
    $driver->versions()->attach(Version::factory()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/drivers/{$driver->id}")
        ->assertStatus(409);

    $this->assertModelExists($driver);
});
