<?php

it('genera il documento OpenAPI con gli endpoint principali', function () {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect($response->json('openapi'))->toStartWith('3.')
        ->and($response->json('paths'))->toHaveKeys(['/models', '/admin/staff']);
});

it('mostra la pagina della documentazione', function () {
    $this->get('/docs/api')->assertOk();
});
