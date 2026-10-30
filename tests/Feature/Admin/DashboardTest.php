<?php

use App\Models\CarModel;
use App\Models\Image;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('il pannello è riservato allo staff', function () {
    $this->getJson('/api/admin/dashboard')->assertUnauthorized();
});

test('conta i modelli per stato, cestino compreso', function () {
    CarModel::factory()->published()->count(2)->create();
    CarModel::factory()->create(['published_at' => null]);
    CarModel::factory()->create(['published_at' => now()->addDays(3)]);
    CarModel::factory()->create(['published_at' => null])->delete();

    $this->actingAs(User::factory()->moderator()->create())
        ->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('data.counts', [
            'published' => 2,
            'drafts' => 1,
            'scheduled' => 1,
            'trashed' => 1,
        ]);
});

test('elenca modelli e versioni programmati, dal più vicino', function () {
    $model = CarModel::factory()->published()->create(['name' => '812']);
    Version::factory()->create([
        'car_model_id' => $model->id,
        'name' => '812 GTS',
        'published_at' => now()->addDay(),
    ]);
    CarModel::factory()->create(['name' => 'F80', 'published_at' => now()->addDays(3)]);
    // Già pubblicato: non è in programma
    CarModel::factory()->published()->create(['name' => 'SF90 Stradale']);

    $scheduled = $this->actingAs(User::factory()->moderator()->create())
        ->getJson('/api/admin/dashboard')
        ->assertOk()
        ->json('data.scheduled');

    expect($scheduled)->toHaveCount(2)
        ->and($scheduled[0])->toMatchArray(['type' => 'version', 'name' => '812 GTS', 'model' => '812'])
        ->and($scheduled[1])->toMatchArray(['type' => 'model', 'name' => 'F80', 'model' => null]);
});

test('elenca le versioni da completare, con tutto ciò che manca', function () {
    // Completa: pubblicata, con foto e scheda tecnica. Non deve comparire
    $complete = Version::factory()->published()->create();
    Image::factory()->cover()->create(['version_id' => $complete->id]);
    VersionSpec::factory()->create(['version_id' => $complete->id]);

    // Bozza, senza foto né scheda
    $empty = Version::factory()->create(['name' => 'SF90 Spider', 'published_at' => null]);

    // Pubblicata, con la copertina, ma senza scheda tecnica
    $noSpecs = Version::factory()->published()->create(['name' => '296 GTB']);
    Image::factory()->cover()->create(['version_id' => $noSpecs->id, 'path' => 'versions/296-gtb.jpg']);

    $items = collect(
        $this->actingAs(User::factory()->moderator()->create())
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->json('data.to_complete')
    )->keyBy('name');

    expect($items)->toHaveCount(2)
        ->and($items['SF90 Spider']['issues'])->toBe(['draft', 'no_photos', 'no_specs'])
        ->and($items['SF90 Spider']['cover'])->toBeNull()
        ->and($items['296 GTB']['issues'])->toBe(['no_specs'])
        ->and($items['296 GTB']['cover'])->toEndWith('/storage/versions/296-gtb.jpg');
});

test('mette prima le schede modificate più di recente', function () {
    Version::factory()->create(['name' => 'Vecchia', 'updated_at' => now()->subWeek()]);
    Version::factory()->create(['name' => 'Recente', 'updated_at' => now()->subHour()]);

    $names = collect(
        $this->actingAs(User::factory()->moderator()->create())
            ->getJson('/api/admin/dashboard')
            ->json('data.to_complete')
    )->pluck('name')->all();

    expect($names)->toBe(['Recente', 'Vecchia']);
});

test("mostra l'attività del catalogo a tutti, quella dello staff solo a chi gestisce le persone", function () {
    $master = User::factory()->master()->create();
    $this->actingAs($master);
    CarModel::factory()->create();
    $moderator = User::factory()->moderator()->create();

    $logsFor = fn (User $user) => collect(
        $this->actingAs($user)->getJson('/api/admin/dashboard')->assertOk()->json('data.activity')
    )->pluck('log')->unique()->sort()->values()->all();

    expect($logsFor($moderator))->toBe(['catalogo'])
        ->and($logsFor($master))->toBe(['catalogo', 'staff']);
});

test('racconta chi ha programmato e chi ha pubblicato, dal più recente', function () {
    $master = User::factory()->master()->create(['name' => 'Matteo']);
    $this->actingAs($master);

    $model = CarModel::factory()->create(['name' => 'F80', 'published_at' => null]);
    // published_at non è assegnabile in massa: lo impostiamo come fa il controller dell'admin
    $model->forceFill(['published_at' => now()->addDay()])->save();
    $model->forceFill(['published_at' => now()->subMinute()])->save();

    $activity = $this->getJson('/api/admin/dashboard')->assertOk()->json('data.activity');

    expect($activity[0])->toMatchArray([
        'log' => 'catalogo',
        'action' => 'published',
        'causer' => 'Matteo',
        'subject_type' => 'car_model',
        'subject_name' => 'F80',
    ])->and($activity[1]['action'])->toBe('scheduled');
});
