<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('crea le tabelle dei cataloghi con le colonne principali', function () {
    expect(Schema::hasColumns('engines', ['code', 'architecture', 'cylinders', 'displacement_cc', 'aspiration']))->toBeTrue()
        ->and(Schema::hasColumns('designers', ['name', 'slug', 'type']))->toBeTrue()
        ->and(Schema::hasColumns('designer_version', ['designer_id', 'version_id', 'role']))->toBeTrue()
        ->and(Schema::hasColumns('drivers', ['first_name', 'last_name', 'slug']))->toBeTrue()
        ->and(Schema::hasColumns('driver_version', ['driver_id', 'version_id']))->toBeTrue();
});
