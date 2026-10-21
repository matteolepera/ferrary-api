<?php

use App\Models\Image;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disco finto: i test non scrivono file veri sul Mac
    Storage::fake('public');

    $this->actingAs(User::factory()->moderator()->create());
    $this->version = Version::factory()->create();
});

/**
 * Carica un'immagine valida nella versione del test.
 */
function uploadImage(Version $version, array $overrides = []): TestResponse
{
    return test()->postJson("/api/admin/versions/{$version->id}/images", [
        'image' => UploadedFile::fake()->image('f40.jpg', 1600, 900),
        'alt_text' => ['it' => 'Ferrari F40 di tre quarti'],
        ...$overrides,
    ]);
}

it('carica un\'immagine e la rende copertina se è la prima', function () {
    $response = uploadImage($this->version)
        ->assertCreated()
        ->assertJsonPath('data.is_cover', true)
        ->assertJsonPath('data.alt_text.it', 'Ferrari F40 di tre quarti');

    $image = Image::findOrFail($response->json('data.id'));

    expect($image->path)->toStartWith("versions/{$this->version->id}/")
        // Il nome del file è generato dal server, non è quello originale
        ->and($image->path)->not->toContain('f40.jpg');

    Storage::disk('public')->assertExists($image->path);
});

it('mantiene una sola copertina', function () {
    $first = uploadImage($this->version)->json('data.id');
    $second = uploadImage($this->version)->json('data.id');
    $third = uploadImage($this->version, ['is_cover' => true])->json('data.id');

    expect(Image::findOrFail($first)->is_cover)->toBeFalse()
        ->and(Image::findOrFail($second)->is_cover)->toBeFalse()
        ->and(Image::findOrFail($third)->is_cover)->toBeTrue();
});

it('rifiuta file non validi', function () {
    // Un PDF
    uploadImage($this->version, ['image' => UploadedFile::fake()->create('scheda.pdf', 100, 'application/pdf')])
        ->assertJsonValidationErrors('image');

    // Un'immagine troppo piccola
    uploadImage($this->version, ['image' => UploadedFile::fake()->image('piccola.jpg', 400, 300)])
        ->assertJsonValidationErrors('image');

    // Un SVG
    uploadImage($this->version, ['image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])
        ->assertJsonValidationErrors('image');
});

it('richiede il testo alternativo in italiano', function () {
    uploadImage($this->version, ['alt_text' => ['en' => 'Only English']])
        ->assertJsonValidationErrors('alt_text.it');
});

it('elenca le immagini della versione in ordine', function () {
    uploadImage($this->version);
    uploadImage($this->version);

    $orders = array_column(
        $this->getJson("/api/admin/versions/{$this->version->id}/images")->assertOk()->json('data'),
        'sort_order',
    );

    expect($orders)->toBe([1, 2]);
});

it('modifica il testo alternativo, rimuovendo le traduzioni svuotate', function () {
    $image = Image::factory()->create([
        'version_id' => $this->version->id,
        'alt_text' => ['it' => 'Vecchio', 'en' => 'Old'],
    ]);

    $this->patchJson("/api/admin/images/{$image->id}", ['alt_text' => ['it' => 'Nuovo testo', 'en' => '']])
        ->assertOk()
        ->assertJsonPath('data.alt_text', ['it' => 'Nuovo testo']);
});

it('sposta la copertina su un\'altra immagine', function () {
    $cover = Image::factory()->cover()->create(['version_id' => $this->version->id]);
    $other = Image::factory()->create(['version_id' => $this->version->id]);

    $this->patchJson("/api/admin/images/{$other->id}", ['is_cover' => true])
        ->assertOk()
        ->assertJsonPath('data.is_cover', true);

    expect($cover->fresh()->is_cover)->toBeFalse();
});

it('non permette di togliere la copertina direttamente', function () {
    $cover = Image::factory()->cover()->create(['version_id' => $this->version->id]);

    $this->patchJson("/api/admin/images/{$cover->id}", ['is_cover' => false])
        ->assertJsonValidationErrors(['is_cover' => 'Per cambiare copertina, imposta come copertina un\'altra immagine.']);

    expect($cover->fresh()->is_cover)->toBeTrue();
});

it('riordina le immagini della versione', function () {
    [$first, $second, $third] = Image::factory()->count(3)->sequence(
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
    )->create(['version_id' => $this->version->id]);

    $this->putJson("/api/admin/versions/{$this->version->id}/images/order", [
        'images' => [$third->id, $first->id, $second->id],
    ])
        ->assertOk();

    $ordered = $this->getJson("/api/admin/versions/{$this->version->id}/images")->json('data');

    expect(array_column($ordered, 'id'))->toBe([$third->id, $first->id, $second->id]);
});

it('rifiuta un elenco incompleto', function () {
    [$first] = Image::factory()->count(2)->create(['version_id' => $this->version->id]);

    $this->putJson("/api/admin/versions/{$this->version->id}/images/order", ['images' => [$first->id]])
        ->assertJsonValidationErrors('images');
});

it('rifiuta un\'immagine di un\'altra versione', function () {
    $own = Image::factory()->create(['version_id' => $this->version->id]);
    $foreign = Image::factory()->create();

    $this->putJson("/api/admin/versions/{$this->version->id}/images/order", ['images' => [$foreign->id]])
        ->assertJsonValidationErrors('images.0');

    expect($own->fresh()->sort_order)->toBe($own->sort_order);
});

it('elimina l\'immagine insieme al suo file', function () {
    $response = uploadImage($this->version);
    $image = Image::findOrFail($response->json('data.id'));

    Storage::disk('public')->assertExists($image->path);

    $this->deleteJson("/api/admin/images/{$image->id}")
        ->assertNoContent();

    $this->assertModelMissing($image);
    Storage::disk('public')->assertMissing($image->path);
});

it('passa la copertina alla prima immagine rimasta', function () {
    $cover = Image::factory()->cover()->create(['version_id' => $this->version->id, 'sort_order' => 1]);
    $last = Image::factory()->create(['version_id' => $this->version->id, 'sort_order' => 3]);
    $next = Image::factory()->create(['version_id' => $this->version->id, 'sort_order' => 2]);

    $this->deleteJson("/api/admin/images/{$cover->id}")
        ->assertNoContent();

    // La nuova copertina è la prima nell'ordine della galleria, non l'ultima creata
    expect($next->fresh()->is_cover)->toBeTrue()
        ->and($last->fresh()->is_cover)->toBeFalse();
});
