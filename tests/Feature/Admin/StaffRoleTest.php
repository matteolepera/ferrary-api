<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('permette al master di promuovere un moderatore ad admin', function () {
    $master = User::factory()->master()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($master)
        ->patchJson("/api/admin/staff/{$moderator->id}/role", ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin');

    expect($moderator->fresh()->role)->toBe(UserRole::Admin);
});

it('impedisce all\'admin di promuovere un moderatore ad admin', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/staff/{$moderator->id}/role", ['role' => 'admin'])
        ->assertForbidden();

    expect($moderator->fresh()->role)->toBe(UserRole::Moderator);
});

it('impedisce di nominare un master con il cambio di ruolo', function () {
    $master = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($master)
        ->patchJson("/api/admin/staff/{$admin->id}/role", ['role' => 'master'])
        ->assertForbidden();
});

it('rifiuta un ruolo inesistente', function () {
    $master = User::factory()->master()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($master)
        ->patchJson("/api/admin/staff/{$moderator->id}/role", ['role' => 'superuser'])
        ->assertJsonValidationErrors('role');
});

it('nega il cambio di ruolo al moderatore prima di validare i dati', function () {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->patchJson("/api/admin/staff/{$other->id}/role", ['role' => 'superuser'])
        ->assertForbidden();
});

it('permette al master di trasferire il proprio ruolo', function () {
    $master = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($master)
        ->postJson("/api/admin/staff/{$admin->id}/transfer-master")
        ->assertOk()
        ->assertJsonPath('data.role', 'master');

    expect($admin->fresh()->isMaster())->toBeTrue()
        ->and($master->fresh()->role)->toBe(UserRole::Admin);
});

it('impedisce all\'admin di trasferire il ruolo di master', function () {
    User::factory()->master()->create();
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($admin)
        ->postJson("/api/admin/staff/{$moderator->id}/transfer-master")
        ->assertForbidden();
});

it('impedisce al master di trasferire il ruolo a sé stesso', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->postJson("/api/admin/staff/{$master->id}/transfer-master")
        ->assertForbidden();
});
