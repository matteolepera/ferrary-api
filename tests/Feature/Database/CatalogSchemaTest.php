<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('crea le tabelle del catalogo con le colonne principali', function () {
    expect(Schema::hasTable('categories'))->toBeTrue()
        ->and(Schema::hasColumns('car_models', ['category_id', 'predecessor_id', 'slug', 'description', 'published_at', 'deleted_at']))->toBeTrue()
        ->and(Schema::hasColumns('versions', ['car_model_id', 'vehicle_type', 'year_start', 'in_production', 'published_at', 'deleted_at']))->toBeTrue();
});
