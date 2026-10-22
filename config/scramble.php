<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\OpenApiVersion;

return [
    /*
     * Which routes to document. String or array form; use Scramble::routes() for custom selection.
     *
     * 'api_path' => [
     *     'include' => 'api',
     *     'exclude' => ['api/internal'],
     * ],
     *
     * Without *, patterns match path segments (api matches api and api/users, not apiary).
     * With *, Str::is is used (e.g. api/v*).
     *
     * One static include → default server is /{include} and paths are stripped (/users).
     * Multiple includes or wildcards → server defaults to / and paths stay full (/api/users).
     * Override with `servers`, or use Scramble::registerApi() for separate bases.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    /*
     * Cache configuration for the generated OpenAPI document.
     *
     * Use `scramble:cache` to warm the cache and `scramble:clear` to invalidate it.
     */
    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],

    /*
     * OpenAPI version of the resulting document.
     */
    'openapi_version' => OpenApiVersion::V3_1,

    'info' => [
        /*
         * Versione dell'API, mostrata nella documentazione.
         */
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Introduzione della documentazione, in Markdown.
         */
        'description' => <<<'MD'
API dell'archivio Ferrary: catalogo pubblico di tutte le Ferrari e area di gestione per lo staff.

Gli endpoint sono divisi in due aree: **Pubblico**, consultabile da chiunque, e **Admin**, riservata allo staff.

## Autenticazione

L'area admin usa la sessione di Laravel Sanctum, con i cookie. Il flusso è:

1. `GET /sanctum/csrf-cookie`: imposta il cookie `XSRF-TOKEN`.
2. `POST /api/login` con email e password, inviando il valore del cookie nell'intestazione `X-XSRF-TOKEN`.
3. Da quel momento ogni richiesta porta con sé il cookie di sessione.

## Convenzioni

**Risposte.** I dati sono sempre nella chiave `data`. Gli elenchi paginati aggiungono `links` e `meta`, con pagina corrente, totale e ultima pagina.

**Codici di stato.**

| Codice | Significato |
|---|---|
| 200, 201, 204 | Operazione riuscita (201: risorsa creata; 204: nessun contenuto) |
| 401 | Serve il login |
| 403 | Il ruolo dell'utente non permette l'operazione |
| 404 | La risorsa non esiste, oppure non è pubblicata |
| 409 | L'operazione è in conflitto con lo stato dei dati, ad esempio una categoria che contiene modelli |
| 422 | Dati non validi: i dettagli sono in `errors`, campo per campo |
| 429 | Troppi tentativi di login |

**Identificativi.** L'API pubblica usa gli slug (`/models/812`), l'area admin gli id numerici (`/admin/models/3`).

**Date.** Formato ISO 8601 (`2026-10-21T15:01:05+00:00`); le date senza orario sono `AAAA-MM-GG`.

**Traduzioni.** L'API pubblica restituisce i testi nella lingua richiesta. L'area admin restituisce e riceve tutte le lingue, nella forma `{"it": "...", "en": "..."}`, e l'italiano è obbligatorio.

## Lingua

Le risposte pubbliche seguono l'intestazione `Accept-Language` (`it` o `en`); senza indicazioni, la lingua è l'italiano.
MD,
    ],

    'ui' => [
        'title' => 'Ferrary API',
    ],

    /*
     * Load Scramble's development tools on documentation pages. An explicit
     * SCRAMBLE_DEV_TOOLS value takes precedence over APP_DEBUG.
     */
    'dev_tools' => [
        'enabled' => env('SCRAMBLE_DEV_TOOLS', env('APP_DEBUG', false)),
    ],

    // Interfaccia della documentazione: 'elements' (predefinita) o 'scalar'
    'renderer' => 'scalar',

    'renderers' => [
        'scalar' => [
            'view' => 'scramble::scalar',
            'theme' => 'default',
            // Testo scuro su fondo chiaro: più riposante per le consultazioni lunghe; l'interruttore resta disponibile
            'darkMode' => false,
            // Necessario perché "Try it" invii il cookie di sessione di Sanctum
            'credentials' => 'include',

            // Interfaccia senza distrazioni
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'hideClientButton' => true,
            'documentDownloadType' => 'json',

            // Navigazione: dentro ogni gruppo, prima le letture (GET) poi le scritture
            'operationsSorter' => 'method',

            // Lettura dei dati
            'orderSchemaPropertiesBy' => 'preserve',
            'orderRequiredPropertiesFirst' => true,
            'expandAllResponses' => true,
            'modelsSectionLabel' => 'Schemi dei dati',

            // Esempi di codice: Axios, quello che useremo in React, come predefinito
            'featuredClients' => ['js/axios', 'shell/curl'],
            'defaultHttpClient' => ['targetKey' => 'js', 'clientKey' => 'axios'],

            'customCss' => file_get_contents(resource_path('css/api-docs.css')),
        ],
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => null,

    /**
     * Determines how Scramble stores the descriptions of enum cases.
     * Available options:
     * - 'description' – Case descriptions are stored as the enum schema's description using table formatting.
     * - 'extension' – Case descriptions are stored in the `x-enumDescriptions` enum schema extension.
     *
     *    @see https://redocly.com/docs-legacy/api-reference-docs/specification-extensions/x-enum-descriptions
     * - false - Case descriptions are ignored.
     */
    'enum_cases_description_strategy' => 'description',

    /**
     * Determines how Scramble stores the names of enum cases.
     * Available options:
     * - 'names' – Case names are stored in the `x-enumNames` enum schema extension.
     * - 'varnames' - Case names are stored in the `x-enum-varnames` enum schema extension.
     * - false - Case names are not stored.
     */
    'enum_cases_names_strategy' => false,

    /**
     * When Scramble encounters deep objects in query parameters, it flattens the parameters so the generated
     * OpenAPI document correctly describes the API. Flattening deep query parameters is relevant until
     * OpenAPI 3.2 is released and query string structure can be described properly.
     *
     * For example, this nested validation rule describes the object with `bar` property:
     * `['foo.bar' => ['required', 'int']]`.
     *
     * When `flatten_deep_query_parameters` is `true`, Scramble will document the parameter like so:
     * `{"name":"foo[bar]", "schema":{"type":"int"}, "required":true}`.
     *
     * When `flatten_deep_query_parameters` is `false`, Scramble will document the parameter like so:
     *  `{"name":"foo", "schema": {"type":"object", "properties":{"bar":{"type": "int"}}, "required": ["bar"]}, "required":true}`.
     */
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    /*
     * Automatically document API security (OpenAPI `security` / `securitySchemes`) based on route
     * middleware.
     *
     * Disabled by default. Uncomment the line below to enable `MiddlewareAuthSecurityStrategy`.
     * When at least one documented route uses middleware matching the configured patterns (by default
     * `auth` and `auth:*`), bearer auth is applied globally. Routes without matching middleware are
     * marked as public (`security: []`).
     *
     * Set to `null` explicitly to disable. If you already configure security manually via
     * `afterOpenApiGenerated` / `extendOpenApi`, keep this disabled to avoid duplicate schemes.
     *
     * Customize with a class-string or [class, options]:
     *
     * 'security_strategy' => [
     *     \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
     *     [
     *         'middleware' => ['auth', 'auth:*'],
     *         'scheme' => \Dedoc\Scramble\Support\Generator\SecurityScheme::http('bearer'),
     *     ],
     * ],
     */
    // 'security_strategy' => \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
    'security_strategy' => null,
];
