<?php

use App\Models\Designer;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
});

it('elenca i designer con il numero di versioni', function () {
    $designer = Designer::factory()->create();
    $designer->versions()->attach(Version::factory()->count(2)->create(), ['role' => 'design']);

    $this->getJson('/api/admin/designers')
        ->assertOk()
        ->assertJsonPath('data.0.versions_count', 2);
});

it('crea un designer con lo slug generato dal nome', function () {
    $this->postJson('/api/admin/designers', [
        'name' => 'Leonardo Fioravanti',
        'type' => 'persona',
        'country' => 'IT',
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'leonardo-fioravanti')
        ->assertJsonPath('data.type', 'persona');
});

it('rifiuta un tipo e un codice paese non validi', function () {
    $this->postJson('/api/admin/designers', [
        'name' => 'Pininfarina',
        'type' => 'azienda',
        'country' => 'it',
    ])
        ->assertJsonValidationErrors(['type', 'country']);
});

it('modifica un designer', function () {
    $designer = Designer::factory()->create();

    $this->patchJson("/api/admin/designers/{$designer->id}", [
        'name' => 'Centro Stile Ferrari',
        'slug' => 'centro-stile-ferrari',
        'type' => 'studio',
        'country' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Centro Stile Ferrari')
        ->assertJsonPath('data.country', null);
});

it('permette all\'admin di eliminare un designer non collegato', function () {
    $designer = Designer::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/designers/{$designer->id}")
        ->assertNoContent();

    $this->assertModelMissing($designer);
});

it('non elimina un designer collegato, anche a una versione nel cestino', function () {
    $designer = Designer::factory()->create();
    $version = Version::factory()->create();
    $designer->versions()->attach($version, ['role' => 'design']);
    $version->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/designers/{$designer->id}")
        ->assertStatus(409);

    $this->assertModelExists($designer);
});
