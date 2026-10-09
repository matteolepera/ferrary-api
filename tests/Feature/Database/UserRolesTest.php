<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assegna moderatore come ruolo predefinito', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(UserRole::Moderator);
});

it('permette un solo master', function () {
    User::factory()->create(['role' => 'master']);

    expect(fn () => User::factory()->create(['role' => 'master']))
        ->toThrow(QueryException::class);
});

it('permette più admin e più moderatori', function () {
    User::factory()->count(2)->create(['role' => 'admin']);
    User::factory()->count(2)->create(['role' => 'moderator']);

    expect(User::count())->toBe(4);
});

it('riconosce il master', function () {
    $master = User::factory()->create(['role' => UserRole::Master]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($master->isMaster())->toBeTrue()
        ->and($admin->isMaster())->toBeFalse();
});

it('ignora il ruolo nelle assegnazioni di massa', function () {
    $user = User::factory()->create();

    $user->fill(['role' => 'master'])->save();

    expect($user->fresh()->role)->toBe(UserRole::Moderator);
});
