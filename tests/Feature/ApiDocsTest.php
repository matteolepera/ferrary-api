<?php

it('genera il documento OpenAPI con gli endpoint principali', function () {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('openapi'))->toStartWith('3.')
        ->and($response->json('paths'))->toHaveKeys(['/models', '/admin/staff']);
});

it('mostra la pagina della documentazione', function () {
    $this->get('/docs/api')->assertOk();
});

it('documenta l\'autenticazione solo sugli endpoint protetti', function () {
    $document = $this->getJson('/docs/api.json')->json();

    expect($document['components']['securitySchemes'])->not->toBeEmpty()
        ->and($document['security'])->not->toBeEmpty()
        // Endpoint pubblici: sicurezza svuotata
        ->and($document['paths']['/models']['get']['security'])->toBe([])
        ->and($document['paths']['/login']['post']['security'])->toBe([])
        // Endpoint protetti: ereditano lo schema generale
        ->and($document['paths']['/admin/staff']['get'])->not->toHaveKey('security');
});
