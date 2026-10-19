<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('permette all\'admin di modificare un moderatore', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$moderator->id}", ['name' => 'Nuovo Nome'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nuovo Nome');

    expect($moderator->fresh()->name)->toBe('Nuovo Nome');
});

it('impedisce all\'admin di modificare un altro admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create(['name' => 'Nome Originale']);

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$otherAdmin->id}", ['name' => 'Nuovo Nome'])
        ->assertForbidden();

    expect($otherAdmin->fresh()->name)->toBe('Nome Originale');
});

it('controlla i permessi prima di validare i dati', function () {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->moderator()->create();

    // Email volutamente non valida: deve arrivare 403, non 422
    $this->actingAs($moderator)
        ->patchJson("/api/admin/staff/{$other->id}", ['email' => 'non-una-email'])
        ->assertForbidden();
});

it('permette di mantenere la stessa email', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$moderator->id}", ['email' => $moderator->email])
        ->assertOk();
});

it('rifiuta l\'email di un altro utente', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$moderator->id}", ['email' => $admin->email])
        ->assertJsonValidationErrors('email');
});

it('aggiorna la password cifrandola', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$moderator->id}", ['password' => 'nuova-password'])
        ->assertOk();

    expect(Hash::check('nuova-password', $moderator->fresh()->password))->toBeTrue();
});

it('permette al master di eliminare un admin', function () {
    $master = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($master)
        ->deleteJson("/api/admin/staff/{$admin->id}")
        ->assertNoContent();

    $this->assertModelMissing($admin);
});

it('impedisce all\'admin di eliminare un altro admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/staff/{$otherAdmin->id}")
        ->assertForbidden();

    $this->assertModelExists($otherAdmin);
});

it('impedisce al master di eliminare sé stesso', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->deleteJson("/api/admin/staff/{$master->id}")
        ->assertForbidden();

    $this->assertModelExists($master);
});
