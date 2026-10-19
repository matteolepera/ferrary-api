<?php

use App\Actions\TransferMasterRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('trasferisce il ruolo di master', function () {
    $oldMaster = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();

    app(TransferMasterRole::class)->handle($admin);

    expect($oldMaster->fresh()->role)->toBe(UserRole::Admin)
        ->and($admin->fresh()->role)->toBe(UserRole::Master)
        ->and(User::where('role', UserRole::Master)->count())->toBe(1);
});

it('nomina il master quando non ce n\'è ancora uno', function () {
    $moderator = User::factory()->moderator()->create();

    app(TransferMasterRole::class)->handle($moderator);

    expect($moderator->fresh()->isMaster())->toBeTrue();
});

it('non cambia nulla se l\'utente è già master', function () {
    $master = User::factory()->master()->create();

    app(TransferMasterRole::class)->handle($master);

    expect($master->fresh()->isMaster())->toBeTrue()
        ->and(User::where('role', UserRole::Master)->count())->toBe(1);
});
