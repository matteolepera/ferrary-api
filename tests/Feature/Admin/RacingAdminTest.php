<?php

use App\Models\User;
use App\Models\Version;
use App\Models\VersionRacing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
    $this->version = Version::factory()->f1()->create();
});

it('elenca i dati sportivi di una versione in ordine di stagione', function () {
    VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'B', 'season_start' => 2005]);
    VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'A', 'season_start' => 2004]);

    $championships = array_column(
        $this->getJson("/api/admin/versions/{$this->version->id}/racing")->assertOk()->json('data'),
        'championship',
    );

    expect($championships)->toBe(['A', 'B']);
});

it('crea un record con le statistiche di squadra', function () {
    // Valori reali della F2004: più podi che gare, perché le vetture sono due
    $this->postJson("/api/admin/versions/{$this->version->id}/racing", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'races' => 18,
        'wins' => 15,
        'poles' => 12,
        'podiums' => 29,
        'notes' => ['it' => 'Titolo piloti e costruttori'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.podiums', 29)
        ->assertJsonPath('data.notes.it', 'Titolo piloti e costruttori');
});

it('rifiuta statistiche incoerenti', function () {
    $this->postJson("/api/admin/versions/{$this->version->id}/racing", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'season_end' => 2003,
        'races' => 18,
        'wins' => 20,
        'poles' => 19,
        'podiums' => 10,
    ])
        ->assertJsonValidationErrors(['season_end', 'wins', 'poles']);
});

it('rifiuta lo stesso campionato due volte nella stessa versione', function () {
    VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'Formula 1']);

    $this->postJson("/api/admin/versions/{$this->version->id}/racing", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
    ])
        ->assertJsonValidationErrors('championship');
});

it('permette lo stesso campionato in un\'altra versione', function () {
    VersionRacing::factory()->create(['championship' => 'Formula 1']);

    $this->postJson("/api/admin/versions/{$this->version->id}/racing", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
    ])
        ->assertCreated();
});

it('sostituisce completamente un record', function () {
    $racing = VersionRacing::factory()->create([
        'version_id' => $this->version->id,
        'championship' => 'Formula 1',
        'races' => 18,
        'wins' => 15,
        'podiums' => 29,
        'poles' => 12,
        'notes' => ['it' => 'Note vecchie'],
    ]);

    $this->patchJson("/api/admin/racing/{$racing->id}", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
        'races' => 18,
    ])
        ->assertOk()
        ->assertJsonPath('data.races', 18)
        // Non inviati: diventano vuoti
        ->assertJsonPath('data.wins', null)
        ->assertJsonPath('data.notes', []);
});

it('permette di mantenere lo stesso campionato', function () {
    $racing = VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'Formula 1']);

    $this->patchJson("/api/admin/racing/{$racing->id}", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
    ])
        ->assertOk();
});

it('rifiuta un campionato già usato da un altro record della versione', function () {
    VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'Formula 1']);
    $other = VersionRacing::factory()->create(['version_id' => $this->version->id, 'championship' => 'WEC']);

    $this->patchJson("/api/admin/racing/{$other->id}", [
        'championship' => 'Formula 1',
        'season_start' => 2004,
    ])
        ->assertJsonValidationErrors('championship');
});

it('elimina un record sportivo', function () {
    $racing = VersionRacing::factory()->create(['version_id' => $this->version->id]);

    $this->deleteJson("/api/admin/racing/{$racing->id}")
        ->assertNoContent();

    $this->assertModelMissing($racing);
});
