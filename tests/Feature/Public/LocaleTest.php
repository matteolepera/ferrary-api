<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    visibleCarModel([
        'description' => ['it' => 'Descrizione italiana', 'en' => 'English description'],
    ]);
});

it('risponde in italiano se la lingua non è indicata', function () {
    // Le richieste dei test includono di default "Accept-Language: en-us":
    // un'intestazione vuota simula un client senza preferenze
    $this->getJson('/api/models', ['Accept-Language' => ''])
        ->assertJsonPath('data.0.description', 'Descrizione italiana');
});

it('risponde in inglese se il client lo preferisce', function () {
    $this->getJson('/api/models', ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertJsonPath('data.0.description', 'English description');
});

it('risponde in italiano per una lingua non supportata', function () {
    $this->getJson('/api/models', ['Accept-Language' => 'de'])
        ->assertJsonPath('data.0.description', 'Descrizione italiana');
});

it('ripiega sull\'italiano se manca la traduzione inglese', function () {
    visibleCarModel([
        'name' => 'AAA',
        'description' => ['it' => 'Solo in italiano'],
    ]);

    // Ordinamento per nome: "AAA" viene per primo
    $this->getJson('/api/models', ['Accept-Language' => 'en'])
        ->assertJsonPath('data.0.description', 'Solo in italiano');
});

it('dichiara la lingua della risposta', function () {
    $this->getJson('/api/models', ['Accept-Language' => 'en'])
        ->assertHeader('Content-Language', 'en');

    $this->getJson('/api/models', ['Accept-Language' => 'de'])
        ->assertHeader('Content-Language', 'it');
});

it('indica alle cache che la risposta dipende dalla lingua', function () {
    $response = $this->getJson('/api/models', ['Accept-Language' => 'en']);

    expect($response->headers->get('Vary'))->toContain('Accept-Language');
});
