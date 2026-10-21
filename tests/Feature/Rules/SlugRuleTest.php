<?php

use App\Rules\Slug;
use Illuminate\Support\Facades\Validator;

it('valida il formato dello slug', function (string $value, bool $valid) {
    $validator = Validator::make(['slug' => $value], ['slug' => [new Slug]]);

    expect($validator->passes())->toBe($valid);
})->with([
    'una parola' => ['f40', true],
    'più parole' => ['812-superfast', true],
    'con numeri' => ['sf90-xx-stradale', true],
    'maiuscole' => ['Ferrari', false],
    'spazi' => ['ferrari f40', false],
    'doppio trattino' => ['ferrari--f40', false],
    'trattino iniziale' => ['-f40', false],
    'trattino finale' => ['f40-', false],
    'underscore' => ['f40_lm', false],
]);
