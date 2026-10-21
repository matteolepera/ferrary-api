<?php

return [
    'master' => [
        'name' => env('MASTER_NAME', 'Master'),
        'email' => env('MASTER_EMAIL'),
        'password' => env('MASTER_PASSWORD'),
    ],

    // Lingue del sito; la prima è quella predefinita
    'locales' => ['it', 'en'],
];
