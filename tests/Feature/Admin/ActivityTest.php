<?php

use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('il registro è riservato a Master e Admin', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->getJson('/api/admin/activity')
        ->assertForbidden();
});

test('mostra le voci dalla più recente, con le modifiche campo per campo', function () {
    $master = User::factory()->master()->create(['name' => 'Matteo']);
    $this->actingAs($master);
    $model = CarModel::factory()->create(['name' => 'F40']);
    $model->update(['name' => 'F40 LM']);

    $this->getJson('/api/admin/activity')
        ->assertOk()
        ->assertJsonPath('data.0.action', 'updated')
        ->assertJsonPath('data.0.causer', 'Matteo')
        ->assertJsonPath('data.0.subject_type', 'car_model')
        ->assertJsonPath('data.0.changes', [['field' => 'name', 'old' => 'F40', 'new' => 'F40 LM']])
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total'], 'links']);
});

test('filtra per registro e per tipo di elemento', function () {
    $master = User::factory()->master()->create();
    $this->actingAs($master);
    CarModel::factory()->create();
    User::factory()->moderator()->create();

    $logs = collect($this->getJson('/api/admin/activity?log=staff')->assertOk()->json('data'))->pluck('log')->unique();
    $types = collect($this->getJson('/api/admin/activity?subject_type=car_model')->assertOk()->json('data'))->pluck('subject_type')->unique();

    expect($logs->all())->toBe(['staff'])
        ->and($types->all())->toBe(['car_model']);
});

test('filtra per persona e per periodo, e rifiuta un periodo al contrario', function () {
    $master = User::factory()->master()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($master);
    CarModel::factory()->create();
    $this->actingAs($admin);
    CarModel::factory()->create();

    $causers = collect($this->getJson("/api/admin/activity?causer_id={$admin->id}")->json('data'))->pluck('causer_id')->unique();
    expect($causers->all())->toBe([$admin->id]);

    $this->getJson('/api/admin/activity?from='.now()->addDay()->toDateString())
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson('/api/admin/activity?from=2026-10-10&to=2026-10-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});
