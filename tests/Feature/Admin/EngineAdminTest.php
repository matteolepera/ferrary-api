<?php

use App\Models\Engine;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionPowertrain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Dati validi per un motore, modificabili caso per caso.
 */
function engineData(array $overrides = []): array
{
    return [
        'code' => 'F140GA',
        'architecture' => 'V',
        'v_angle_deg' => 65,
        'cylinders' => 12,
        'displacement_cc' => 6496,
        'aspiration' => 'aspirato',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
});

it('elenca i motori con il numero di versioni', function () {
    $engine = Engine::factory()->create();
    VersionPowertrain::factory()->count(2)->create(['engine_id' => $engine->id]);

    $this->getJson('/api/admin/engines')
        ->assertOk()
        ->assertJsonPath('data.0.versions_count', 2);
});

it('crea un motore', function () {
    $this->postJson('/api/admin/engines', engineData(['description' => ['it' => 'V12 aspirato']]))
        ->assertCreated()
        ->assertJsonPath('data.code', 'F140GA')
        ->assertJsonPath('data.architecture', 'V');
});

it('rifiuta l\'angolo della V su un motore piatto e i codici duplicati', function () {
    Engine::factory()->create(['code' => 'F140GA']);

    $this->postJson('/api/admin/engines', engineData(['architecture' => 'piatto']))
        ->assertJsonValidationErrors(['v_angle_deg', 'code']);
});

it('sostituisce completamente un motore', function () {
    $engine = Engine::factory()->create(['name' => 'Nome vecchio']);

    $this->patchJson("/api/admin/engines/{$engine->id}", engineData(['code' => $engine->code]))
        ->assertOk()
        // Non inviato: diventa vuoto
        ->assertJsonPath('data.name', null);
});

it('impedisce al moderatore di eliminare un motore', function () {
    $engine = Engine::factory()->create();

    $this->deleteJson("/api/admin/engines/{$engine->id}")->assertForbidden();
});

it('permette all\'admin di eliminare un motore inutilizzato', function () {
    $engine = Engine::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/engines/{$engine->id}")
        ->assertNoContent();

    $this->assertModelMissing($engine);
});

it('non elimina un motore usato, anche da una versione nel cestino', function () {
    $engine = Engine::factory()->create();
    $powertrain = VersionPowertrain::factory()->create(['engine_id' => $engine->id]);
    Version::findOrFail($powertrain->version_id)->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/api/admin/engines/{$engine->id}")
        ->assertStatus(409);

    $this->assertModelExists($engine);
});
