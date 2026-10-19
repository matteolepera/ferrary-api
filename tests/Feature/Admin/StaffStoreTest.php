<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Dati validi riusati in tutti i test: ogni test cambia solo quello che gli serve
beforeEach(function () {
    $this->validData = [
        'name' => 'Giulia Rossi',
        'email' => 'giulia@ferrary.test',
        'password' => 'password-sicura',
    ];
});

it('permette al master di creare un admin', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'admin'])
        ->assertCreated()
        ->assertJsonPath('data.role', 'admin');

    $user = User::where('email', 'giulia@ferrary.test')->firstOrFail();

    // La password deve essere salvata cifrata, mai in chiaro
    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->password)->not->toBe('password-sicura')
        ->and(Hash::check('password-sicura', $user->password))->toBeTrue();
});

it('permette all\'admin di creare un moderatore', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'moderator'])
        ->assertCreated();
});

it('impedisce all\'admin di creare un altro admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'admin'])
        ->assertForbidden();

    // Verifica che, oltre al 403, l'utente non sia stato creato davvero
    $this->assertDatabaseMissing('users', ['email' => 'giulia@ferrary.test']);
});

it('impedisce di creare un master', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'master'])
        ->assertForbidden();
});

it('impedisce al moderatore di creare utenti', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'moderator'])
        ->assertForbidden();
});

it('richiede tutti i campi', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->postJson('/api/admin/staff', [])
        ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

it('rifiuta un\'email già usata', function () {
    $master = User::factory()->master()->create();
    User::factory()->create(['email' => 'giulia@ferrary.test']);

    $this->actingAs($master)
        ->postJson('/api/admin/staff', [...$this->validData, 'role' => 'moderator'])
        ->assertJsonValidationErrors('email');
});
