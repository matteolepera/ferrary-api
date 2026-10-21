<?php

use App\Models\CarModel;
use App\Models\Engine;
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

it('crea una versione con specifiche e motorizzazione in una sola richiesta', function () {
    $model = CarModel::factory()->create();
    $engine = Engine::factory()->create();

    $this->postJson("/api/admin/models/{$model->id}/versions", [
        'name' => 'SF90 Stradale',
        'vehicle_type' => 'stradale',
        'year_start' => 2019,
        'specs' => ['top_speed_kmh' => 340, 'zero_to_100_sec' => 2.5, 'zero_to_200_sec' => 6.7],
        'powertrain' => ['engine_id' => $engine->id, 'ice_power_cv' => 780, 'electric_power_cv' => 220],
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'sf90-stradale')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.specs.top_speed_kmh', 340)
        ->assertJsonPath('data.powertrain.powertrain_type', 'ibrido');
});

it('crea una versione senza specifiche né motorizzazione', function () {
    $model = CarModel::factory()->create();

    $this->postJson("/api/admin/models/{$model->id}/versions", [
        'name' => 'F40',
        'vehicle_type' => 'stradale',
        'year_start' => 1987,
    ])
        ->assertCreated()
        ->assertJsonPath('data.specs', null)
        ->assertJsonPath('data.powertrain', null);
});

it('rifiuta dati della versione incoerenti', function () {
    $model = CarModel::factory()->create();

    $this->postJson("/api/admin/models/{$model->id}/versions", [
        'name' => 'Test',
        'vehicle_type' => 'f1',
        'body_type' => 'coupe',
        'year_start' => 2020,
        'year_end' => 2015,
        'production_type' => 'one_off',
        'units_produced' => 5,
    ])
        ->assertJsonValidationErrors(['body_type', 'year_end', 'units_produced']);
});

it('rifiuta una motorizzazione incoerente', function () {
    $model = CarModel::factory()->create();

    $this->postJson("/api/admin/models/{$model->id}/versions", [
        'name' => 'Test',
        'vehicle_type' => 'stradale',
        'year_start' => 2020,
        'specs' => ['zero_to_100_sec' => 3.0, 'zero_to_200_sec' => 2.0],
        // Potenza termica e batteria senza motore e senza parte elettrica
        'powertrain' => ['ice_power_cv' => 600, 'battery_kwh' => 7.9],
    ])
        ->assertJsonValidationErrors([
            'specs.zero_to_200_sec',
            'powertrain.engine_id',
            'powertrain.ice_power_cv',
            'powertrain.battery_kwh',
        ]);
});

it('annulla tutto se una parte del salvataggio fallisce', function () {
    $model = CarModel::factory()->create();

    // Simula un errore durante il salvataggio della motorizzazione
    VersionPowertrain::creating(fn () => throw new RuntimeException('Errore simulato'));

    $this->withoutExceptionHandling();

    expect(fn () => $this->postJson("/api/admin/models/{$model->id}/versions", [
        'name' => 'Test',
        'vehicle_type' => 'stradale',
        'year_start' => 2020,
        'powertrain' => ['electric_power_cv' => 800],
    ]))->toThrow(RuntimeException::class);

    // La versione era stata salvata prima della motorizzazione: la transazione l'ha annullata
    expect(Version::count())->toBe(0);
});

it('sostituisce completamente la sezione delle specifiche', function () {
    $version = Version::factory()->create();
    VersionSpec::factory()->create(['version_id' => $version->id, 'top_speed_kmh' => 300, 'brakes' => 'carboceramici']);

    $this->patchJson("/api/admin/versions/{$version->id}", ['specs' => ['top_speed_kmh' => 340]])
        ->assertOk()
        ->assertJsonPath('data.specs.top_speed_kmh', 340)
        // Non inviato: diventa vuoto
        ->assertJsonPath('data.specs.brakes', null);
});

it('elimina una sezione inviata come null', function () {
    $version = Version::factory()->create();
    VersionPowertrain::factory()->create(['version_id' => $version->id]);

    $this->patchJson("/api/admin/versions/{$version->id}", ['powertrain' => null])
        ->assertOk()
        ->assertJsonPath('data.powertrain', null);

    expect(VersionPowertrain::count())->toBe(0);
});

it('crea una sezione che prima non c\'era', function () {
    $version = Version::factory()->create();

    $this->patchJson("/api/admin/versions/{$version->id}", ['powertrain' => ['electric_power_cv' => 800]])
        ->assertOk()
        ->assertJsonPath('data.powertrain.powertrain_type', 'elettrico');
});

it('controlla la coerenza con i valori già salvati', function () {
    $version = Version::factory()->create(['year_start' => 2015, 'year_end' => 2020, 'in_production' => false]);

    // L'anno di fine già salvato non è compatibile con "in produzione"
    $this->patchJson("/api/admin/versions/{$version->id}", ['in_production' => true])
        ->assertJsonValidationErrors('year_end');

    // Il nuovo anno di inizio sarebbe dopo l'anno di fine già salvato
    $this->patchJson("/api/admin/versions/{$version->id}", ['year_start' => 2025])
        ->assertJsonValidationErrors('year_end');
});

it('permette di modificare insieme i campi legati da vincoli', function () {
    $version = Version::factory()->create(['year_start' => 2015, 'year_end' => 2020, 'in_production' => false]);

    $this->patchJson("/api/admin/versions/{$version->id}", ['in_production' => true, 'year_end' => null])
        ->assertOk()
        ->assertJsonPath('data.in_production', true)
        ->assertJsonPath('data.year_end', null);
});
