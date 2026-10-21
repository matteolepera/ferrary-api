<?php

use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->model = CarModel::factory()->create(['published_at' => null]);
});

it('pubblica subito un modello', function () {
    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$this->model->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    expect($this->model->fresh()->isPublished())->toBeTrue();
});

it('programma la pubblicazione per una data futura', function () {
    $date = now()->addWeek()->startOfMinute();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$this->model->id}/publish", ['published_at' => $date->toIso8601String()])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled');

    expect($this->model->fresh()->published_at->equalTo($date))->toBeTrue();
});

it('ritira un modello pubblicato', function () {
    $this->model->update(['name' => $this->model->name]);
    $this->model->forceFill(['published_at' => now()->subDay()])->save();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$this->model->id}/unpublish")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

it('impedisce al moderatore di pubblicare', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->postJson("/api/admin/models/{$this->model->id}/publish")
        ->assertForbidden();

    expect($this->model->fresh()->published_at)->toBeNull();
});

it('rifiuta una data non valida', function () {
    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$this->model->id}/publish", ['published_at' => 'domani mattina'])
        ->assertJsonValidationErrors('published_at');
});

it('registra chi ha pubblicato', function () {
    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$this->model->id}/publish")
        ->assertOk();

    $activity = Activity::forSubject($this->model)->forEvent('updated')->latest('id')->firstOrFail();

    expect($activity->causer->is($this->admin))->toBeTrue()
        ->and($activity->attribute_changes['attributes'])->toHaveKey('published_at');
});

it('pubblica subito una versione', function () {
    $version = Version::factory()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/versions/{$version->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');
});

it('programma la pubblicazione di una versione', function () {
    $version = Version::factory()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/versions/{$version->id}/publish", [
            'published_at' => now()->addDays(3)->toIso8601String(),
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled');
});

it('ritira una versione pubblicata', function () {
    $version = Version::factory()->published()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/versions/{$version->id}/unpublish")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

it('impedisce al moderatore di pubblicare una versione', function () {
    $version = Version::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->postJson("/api/admin/versions/{$version->id}/publish")
        ->assertForbidden();

    expect($version->fresh()->published_at)->toBeNull();
});
