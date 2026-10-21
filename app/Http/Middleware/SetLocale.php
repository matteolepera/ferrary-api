<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Imposta la lingua dell'app in base ad Accept-Language e la dichiara nella risposta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(config('ferrary.locales')) ?? config('app.locale');

        app()->setLocale($locale);

        $response = $next($request);

        // Dichiara la lingua usata nella risposta
        $response->headers->set('Content-Language', $locale);

        // La risposta cambia con la lingua richiesta: le cache devono tenerne conto
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
