<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assegna moderatore come ruolo predefinito', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe('moderator');
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
