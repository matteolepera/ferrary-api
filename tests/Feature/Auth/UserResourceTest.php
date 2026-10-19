<?php

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('espone solo i campi pubblici dell\'utente', function () {
    $user = User::factory()->admin()->create();

    $data = (new UserResource($user))->resolve();

    expect(array_keys($data))->toBe(['id', 'name', 'email', 'role'])
        ->and($data['role'])->toBe(UserRole::Admin);
});
