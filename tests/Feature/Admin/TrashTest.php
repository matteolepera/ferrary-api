<?php

use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('sposta nel cestino il modello insieme alle sue versioni', function () {
    $model = CarModel::factory()->create();
    $versions = Version::factory()->count(2)->create(['car_model_id' => $model->id]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/models/{$model->id}")
        ->assertNoContent();

    $model->refresh();

    expect($model->trashed())->toBeTrue();

    foreach ($versions as $version) {
        $version = Version::withTrashed()->findOrFail($version->id);

        // Stesso deleted_at del modello: il segno per ripristinarle insieme
        expect($version->deleted_at->equalTo($model->deleted_at))->toBeTrue();
    }
});

it('non cambia la data delle versioni già nel cestino', function () {
    $model = CarModel::factory()->create();
    $old = Version::factory()->create(['car_model_id' => $model->id]);
    $old->delete();
    $oldDate = Version::withTrashed()->findOrFail($old->id)->deleted_at;

    $this->travel(1)->days();

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/models/{$model->id}")
        ->assertNoContent();

    expect(Version::withTrashed()->findOrFail($old->id)->deleted_at->equalTo($oldDate))->toBeTrue();
});

it('sposta nel cestino una singola versione', function () {
    $version = Version::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/versions/{$version->id}")
        ->assertNoContent();

    expect($version->fresh()->trashed())->toBeTrue()
        ->and(CarModel::findOrFail($version->car_model_id)->trashed())->toBeFalse();
});

it('impedisce al moderatore di spostare nel cestino', function () {
    $model = CarModel::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->deleteJson("/api/admin/models/{$model->id}")
        ->assertForbidden();

    expect($model->fresh()->trashed())->toBeFalse();
});

it('nasconde i modelli nel cestino dall\'elenco dell\'admin', function () {
    CarModel::factory()->create()->delete();
    CarModel::factory()->create();

    $this->actingAs($this->admin)
        ->getJson('/api/admin/models')
        ->assertJsonCount(1, 'data');
});
