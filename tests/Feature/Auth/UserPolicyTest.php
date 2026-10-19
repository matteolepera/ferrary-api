<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applica la gerarchia nella gestione dello staff', function (UserRole $actor, UserRole $target, bool $expected) {
    $user = User::factory()->create(['role' => $actor]);
    $other = User::factory()->create(['role' => $target]);

    expect($user->can('update', $other))->toBe($expected)
        ->and($user->can('delete', $other))->toBe($expected);
})->with([
    'master su admin' => [UserRole::Master, UserRole::Admin, true],
    'master su moderatore' => [UserRole::Master, UserRole::Moderator, true],
    'admin su moderatore' => [UserRole::Admin, UserRole::Moderator, true],
    'admin su admin' => [UserRole::Admin, UserRole::Admin, false],
    'admin su master' => [UserRole::Admin, UserRole::Master, false],
    'moderatore su moderatore' => [UserRole::Moderator, UserRole::Moderator, false],
    'moderatore su admin' => [UserRole::Moderator, UserRole::Admin, false],
]);

it('limita i ruoli assegnabili in creazione', function (UserRole $actor, UserRole $role, bool $expected) {
    $user = User::factory()->create(['role' => $actor]);

    expect($user->can('create', [User::class, $role]))->toBe($expected);
})->with([
    'master crea admin' => [UserRole::Master, UserRole::Admin, true],
    'master crea moderatore' => [UserRole::Master, UserRole::Moderator, true],
    'master crea master' => [UserRole::Master, UserRole::Master, false],
    'admin crea moderatore' => [UserRole::Admin, UserRole::Moderator, true],
    'admin crea admin' => [UserRole::Admin, UserRole::Admin, false],
    'moderatore crea moderatore' => [UserRole::Moderator, UserRole::Moderator, false],
]);

it('limita i cambi di ruolo', function (UserRole $actor, UserRole $current, UserRole $new, bool $expected) {
    $user = User::factory()->create(['role' => $actor]);
    $other = User::factory()->create(['role' => $current]);

    expect($user->can('changeRole', [$other, $new]))->toBe($expected);
})->with([
    'master promuove un moderatore ad admin' => [UserRole::Master, UserRole::Moderator, UserRole::Admin, true],
    'admin promuove un moderatore ad admin' => [UserRole::Admin, UserRole::Moderator, UserRole::Admin, false],
    'master nomina master con un cambio di ruolo' => [UserRole::Master, UserRole::Admin, UserRole::Master, false],
]);

it('impedisce al master di eliminare sé stesso', function () {
    $master = User::factory()->master()->create();

    expect($master->can('delete', $master))->toBeFalse();
});

it('riserva al master il trasferimento del ruolo', function () {
    $master = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    expect($master->can('transferMaster', $admin))->toBeTrue()
        ->and($admin->can('transferMaster', $moderator))->toBeFalse();
});

it('nasconde l\'elenco dello staff ai moderatori', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($moderator->can('viewAny', User::class))->toBeFalse();
});
