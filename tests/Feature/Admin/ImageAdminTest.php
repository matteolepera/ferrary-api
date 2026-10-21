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
