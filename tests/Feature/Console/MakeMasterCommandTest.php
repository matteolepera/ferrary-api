<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('trasferisce il ruolo a un utente esistente', function () {
    $oldMaster = User::factory()->master()->create(['name' => 'Anna']);
    $admin = User::factory()->admin()->create(['name' => 'Luca']);

    $this->artisan('app:make-master', ['email' => $admin->email])
        ->expectsConfirmation('Il ruolo di master passerà da Anna a Luca, e Anna diventerà admin. Confermi?', 'yes')
        ->assertSuccessful();

    expect($admin->fresh()->isMaster())->toBeTrue()
        ->and($oldMaster->fresh()->role)->toBe(UserRole::Admin);
});

it('crea un nuovo utente master', function () {
    $this->artisan('app:make-master', ['email' => 'nuovo@ferrary.test'])
        ->expectsConfirmation('Nessun utente con email nuovo@ferrary.test. Vuoi crearlo?', 'yes')
        ->expectsQuestion('Nome', 'Matteo')
        ->expectsQuestion('Password', 'password-sicura')
        ->assertSuccessful();

    $user = User::where('email', 'nuovo@ferrary.test')->firstOrFail();

    expect($user->isMaster())->toBeTrue()
        ->and(Hash::check('password-sicura', $user->password))->toBeTrue();
});

it('non cambia nulla se l\'operazione viene annullata', function () {
    $master = User::factory()->master()->create(['name' => 'Anna']);
    $admin = User::factory()->admin()->create(['name' => 'Luca']);

    $this->artisan('app:make-master', ['email' => $admin->email])
        ->expectsConfirmation('Il ruolo di master passerà da Anna a Luca, e Anna diventerà admin. Confermi?', 'no')
        ->assertFailed();

    expect($master->fresh()->isMaster())->toBeTrue()
        ->and($admin->fresh()->role)->toBe(UserRole::Admin);
});

it('rifiuta un indirizzo email non valido', function () {
    $this->artisan('app:make-master', ['email' => 'non-una-email'])
        ->assertFailed();
});
