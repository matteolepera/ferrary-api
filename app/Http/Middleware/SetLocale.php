<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Imposta la lingua dell'app in base all'intestazione Accept-Language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sceglie tra le lingue supportate quella preferita dal client;
        // se nessuna corrisponde, restituisce la prima dell'elenco (l'italiano)
        $locale = $request->getPreferredLanguage(config('ferrary.locales'));

        app()->setLocale($locale ?? config('app.locale'));

        return $next($request);
    }
}
