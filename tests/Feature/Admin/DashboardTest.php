<?php

use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
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
