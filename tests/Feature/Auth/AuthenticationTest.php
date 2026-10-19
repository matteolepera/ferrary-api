<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost:5173');
});

it('effettua il login con le credenziali corrette', function () {
    $user = User::factory()->admin()->create(['password' => 'password-corretta']);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password-corretta',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', 'admin');

    $this->assertAuthenticatedAs($user);
});

it('rifiuta le credenziali sbagliate', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password-sbagliata',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('richiede email e password', function () {
    $this->postJson('/api/login', [])
        ->assertJsonValidationErrors(['email', 'password']);
});

it('blocca i tentativi dopo il quinto in un minuto', function () {
    $user = User::factory()->create();
    $credentials = ['email' => $user->email, 'password' => 'password-sbagliata'];

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', $credentials)->assertUnprocessable();
    }

    $this->postJson('/api/login', $credentials)->assertTooManyRequests();
});

it('restituisce l\'utente corrente', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('nega l\'utente corrente senza login', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('effettua il logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});
