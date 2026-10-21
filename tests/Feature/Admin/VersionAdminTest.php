<?php

use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionPowertrain;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->moderator()->create());
});

it('elenca le versioni di un modello, bozze comprese, in ordine', function () {
    $model = CarModel::factory()->create();
    Version::factory()->published()->create(['car_model_id' => $model->id, 'name' => 'GTS', 'sort_order' => 2]);
    Version::factory()->create(['car_model_id' => $model->id, 'name' => 'Superfast', 'sort_order' => 1]);
    // Versione di un altro modello: non deve comparire
    Version::factory()->create();

    $names = array_column($this->getJson("/api/admin/models/{$model->id}/versions")->assertOk()->json('data'), 'name');

    expect($names)->toBe(['Superfast', 'GTS']);
});

it('mostra una versione con specifiche e motorizzazione in forma piatta', function () {
    $version = Version::factory()->create(['description' => ['it' => 'Descrizione', 'en' => 'Description']]);
    VersionSpec::factory()->create(['version_id' => $version->id, 'brakes' => 'carboceramici', 'zero_to_100_sec' => 2.9]);
    VersionPowertrain::factory()->hybrid()->create(['version_id' => $version->id]);

    $this->getJson("/api/admin/versions/{$version->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.description.en', 'Description')
        ->assertJsonPath('data.specs.brakes', 'carboceramici')
        ->assertJsonPath('data.specs.zero_to_100_sec', 2.9)
        ->assertJsonPath('data.powertrain.powertrain_type', 'ibrido')
        ->assertJsonMissingPath('data.specs.version_id');
});

it('restituisce specifiche e motorizzazione vuote se mancano', function () {
    $version = Version::factory()->create();

    $this->getJson("/api/admin/versions/{$version->id}")
        ->assertJsonPath('data.specs', null)
        ->assertJsonPath('data.powertrain', null);
});

it('richiede il login', function () {
    Auth::forgetGuards();

    $this->getJson('/api/admin/versions/1')->assertUnauthorized();
});
