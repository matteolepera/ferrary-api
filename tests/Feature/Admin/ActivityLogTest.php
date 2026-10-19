<?php

use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('registra chi crea un membro dello staff', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)
        ->postJson('/api/admin/staff', [
            'name' => 'Giulia Rossi',
            'email' => 'giulia@ferrary.test',
            'password' => 'password-sicura',
            'role' => 'moderator',
        ])
        ->assertCreated();

    // L'ultima creazione nel log "staff" è quella appena fatta tramite l'API
    $activity = Activity::inLog('staff')->forEvent('created')->latest('id')->firstOrFail();

    expect($activity->causer->is($master))->toBeTrue()
        ->and($activity->subject->email)->toBe('giulia@ferrary.test');
});

it('registra i valori prima e dopo una modifica al catalogo', function () {
    $moderator = User::factory()->moderator()->create();
    $version = Version::factory()->create(['name' => 'F40']);

    // Da qui in poi le modifiche risultano fatte dal moderatore
    $this->actingAs($moderator);
    $version->update(['name' => 'F40 LM']);

    $activity = Activity::forSubject($version)->forEvent('updated')->firstOrFail();

    expect($activity->causer->is($moderator))->toBeTrue()
        ->and($activity->attribute_changes['old']['name'])->toBe('F40')
        ->and($activity->attribute_changes['attributes']['name'])->toBe('F40 LM');
});

it('non registra mai la password', function () {
    $user = User::factory()->moderator()->create();

    $user->update(['password' => 'nuova-password']);

    $created = Activity::forSubject($user)->forEvent('created')->firstOrFail();

    // Alla creazione la password non compare tra i valori registrati...
    expect($created->attribute_changes['attributes'])->not->toHaveKey('password')
        // ...e cambiare solo la password non produce nessuna riga di modifica
        ->and(Activity::forSubject($user)->forEvent('updated')->exists())->toBeFalse();
});

it('registra lo spostamento nel cestino e il ripristino', function () {
    $model = CarModel::factory()->create();

    $model->delete();
    $model->restore();

    expect(Activity::forSubject($model)->pluck('event')->all())
        ->toContain('deleted', 'restored');
});
