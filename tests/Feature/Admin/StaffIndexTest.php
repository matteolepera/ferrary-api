<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra lo staff all\'admin, con il master in cima', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->moderator()->create();
    User::factory()->master()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/staff')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.role', 'master');
});

it('nega l\'elenco dello staff ai moderatori', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->getJson('/api/admin/staff')
        ->assertForbidden();
});

it('nega l\'elenco dello staff a chi non ha fatto il login', function () {
    $this->getJson('/api/admin/staff')->assertUnauthorized();
});

it('permette al moderatore di vedere il proprio profilo', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->getJson("/api/admin/staff/{$moderator->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $moderator->id);
});

it('nega al moderatore il profilo degli altri', function () {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->getJson("/api/admin/staff/{$other->id}")
        ->assertForbidden();
});
