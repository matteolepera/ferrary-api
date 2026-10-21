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

it('mostra modelli e versioni cestinate da sole', function () {
    // Modello cestinato con la sua versione: compare solo il modello
    $trashedModel = CarModel::factory()->create();
    Version::factory()->create(['car_model_id' => $trashedModel->id]);
    $trashedModel->delete();

    // Versione cestinata da sola, con il modello attivo
    $trashedVersion = Version::factory()->create();
    $trashedVersion->delete();

    // Elementi attivi: non compaiono
    Version::factory()->create();

    $this->actingAs($this->admin)
        ->getJson('/api/admin/trash')
        ->assertOk()
        ->assertJsonCount(1, 'models')
        ->assertJsonPath('models.0.id', $trashedModel->id)
        ->assertJsonCount(1, 'versions')
        ->assertJsonPath('versions.0.id', $trashedVersion->id)
        ->assertJsonPath('versions.0.model.id', $trashedVersion->car_model_id);
});

it('nasconde il cestino ai moderatori', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->getJson('/api/admin/trash')
        ->assertForbidden();
});

it('ripristina il modello con le sole versioni cestinate insieme a lui', function () {
    $model = CarModel::factory()->create();
    $together = Version::factory()->create(['car_model_id' => $model->id]);
    $alone = Version::factory()->create(['car_model_id' => $model->id]);
    $alone->delete();

    $this->travel(1)->days();
    $model->delete();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/models/{$model->id}/restore")
        ->assertOk();

    expect($model->fresh()->trashed())->toBeFalse()
        ->and(Version::withTrashed()->findOrFail($together->id)->trashed())->toBeFalse()
        // Era stata cestinata prima, da sola: resta nel cestino
        ->and(Version::withTrashed()->findOrFail($alone->id)->trashed())->toBeTrue();
});

it('ripristina una versione cestinata da sola', function () {
    $version = Version::factory()->create();
    $version->delete();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/versions/{$version->id}/restore")
        ->assertOk();

    expect($version->fresh()->trashed())->toBeFalse();
});

it('non ripristina una versione il cui modello è nel cestino', function () {
    $model = CarModel::factory()->create();
    $version = Version::factory()->create(['car_model_id' => $model->id]);
    $model->delete();

    $this->actingAs($this->admin)
        ->postJson("/api/admin/versions/{$version->id}/restore")
        ->assertStatus(409);

    expect(Version::withTrashed()->findOrFail($version->id)->trashed())->toBeTrue();
});

it('impedisce al moderatore di ripristinare', function () {
    $model = CarModel::factory()->create();
    $model->delete();

    $this->actingAs(User::factory()->moderator()->create())
        ->postJson("/api/admin/models/{$model->id}/restore")
        ->assertForbidden();
});
