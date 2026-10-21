<?php

use App\Models\Designer;
use App\Models\Driver;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
    $this->version = Version::factory()->create();
});

it('sostituisce i designer, anche con due ruoli per lo stesso designer', function () {
    $old = Designer::factory()->create();
    $this->version->designers()->attach($old, ['role' => 'design']);
    $pininfarina = Designer::factory()->create(['name' => 'Pininfarina']);

    $this->putJson("/api/admin/versions/{$this->version->id}/designers", [
        'designers' => [
            ['id' => $pininfarina->id, 'role' => 'design'],
            ['id' => $pininfarina->id, 'role' => 'carrozzeria'],
        ],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // Il vecchio designer non è più collegato
    expect($this->version->designers()->where('designers.id', $old->id)->exists())->toBeFalse();
});

it('toglie tutti i designer con un elenco vuoto', function () {
    $this->version->designers()->attach(Designer::factory()->create(), ['role' => 'design']);

    $this->putJson("/api/admin/versions/{$this->version->id}/designers", ['designers' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rifiuta designer inesistenti e ruoli non validi', function () {
    $this->putJson("/api/admin/versions/{$this->version->id}/designers", [
        'designers' => [['id' => 999, 'role' => 'pittore']],
    ])
        ->assertJsonValidationErrors(['designers.0.id', 'designers.0.role']);
});

it('sostituisce i piloti della versione', function () {
    $old = Driver::factory()->create();
    $this->version->drivers()->attach($old);
    $new = Driver::factory()->count(2)->create();

    $this->putJson("/api/admin/versions/{$this->version->id}/drivers", [
        'drivers' => $new->pluck('id')->all(),
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($this->version->drivers()->pluck('drivers.id')->all())
        ->toEqualCanonicalizing($new->pluck('id')->all());
});

it('mostra designer e piloti nella scheda della versione', function () {
    $this->version->designers()->attach(Designer::factory()->create(), ['role' => 'carrozzeria']);
    $this->version->drivers()->attach(Driver::factory()->create());

    $this->getJson("/api/admin/versions/{$this->version->id}")
        ->assertJsonPath('data.designers.0.role', 'carrozzeria')
        ->assertJsonCount(1, 'data.drivers');
});
