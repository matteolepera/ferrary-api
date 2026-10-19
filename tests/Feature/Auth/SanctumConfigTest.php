<?php

it('fornisce il cookie CSRF al frontend', function () {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->get('/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('autorizza il frontend con CORS e credenziali', function () {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->get('/sanctum/csrf-cookie')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

it('non autorizza le origini sconosciute', function () {
    $response = $this->withHeader('Origin', 'http://sito-malevolo.test')
        ->get('/sanctum/csrf-cookie');

    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('http://sito-malevolo.test');
});
