<?php

use App\Enums\UserRole;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Designer;
use App\Models\Driver;
use App\Models\Engine;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applica i permessi sul catalogo in base al ruolo', function (UserRole $role, string $ability, bool $expected) {
    $user = User::factory()->create(['role' => $role]);
    $version = Version::factory()->create();

    expect($user->can($ability, $version))->toBe($expected);
})->with([
    'moderatore modifica' => [UserRole::Moderator, 'update', true],
    'moderatore pubblica' => [UserRole::Moderator, 'publish', false],
    'moderatore sposta nel cestino' => [UserRole::Moderator, 'delete', false],
    'moderatore ripristina' => [UserRole::Moderator, 'restore', false],
    'moderatore elimina definitivamente' => [UserRole::Moderator, 'forceDelete', false],
    'admin modifica' => [UserRole::Admin, 'update', true],
    'admin pubblica' => [UserRole::Admin, 'publish', true],
    'admin sposta nel cestino' => [UserRole::Admin, 'delete', true],
    'admin ripristina' => [UserRole::Admin, 'restore', true],
    'admin elimina definitivamente' => [UserRole::Admin, 'forceDelete', false],
    'master pubblica' => [UserRole::Master, 'publish', true],
    'master elimina definitivamente' => [UserRole::Master, 'forceDelete', true],
]);

it('permette a tutto lo staff di creare schede', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    expect($user->can('create', Version::class))->toBeTrue();
})->with([UserRole::Moderator, UserRole::Admin, UserRole::Master]);

it('usa le stesse regole per tutti i model del catalogo', function (string $model) {
    $record = $model::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();

    expect($moderator->can('delete', $record))->toBeFalse()
        ->and($admin->can('delete', $record))->toBeTrue();
})->with([
    'modello' => CarModel::class,
    'versione' => Version::class,
    'categoria' => Category::class,
    'motore' => Engine::class,
    'designer' => Designer::class,
    'pilota' => Driver::class,
]);
