<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarModelResource;
use App\Models\CarModel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoint pubblici dei modelli: accessibili senza login, mostrano solo contenuti pubblicati.
 */
class CarModelController extends Controller
{
    /**
     * Elenco paginato dei modelli visibili al pubblico.
     */
    public function index(): AnonymousResourceCollection
    {
        $models = CarModel::query()
            // Solo modelli pubblicati (scope del trait Publishable)...
            ->published()
            // ...che abbiano almeno una versione pubblicata: niente schede vuote.
            // $query non ha tipo: Laravel ci passa una query sulle versioni, ma un tipo
            // generico come "Builder" non direbbe a Larastan che è proprio una query su Version.
            ->whereHas('versions', fn ($query) => $query->published())
            // Carica in anticipo categorie, versioni principali e loro copertine:
            // tre query in tutto, qualunque sia il numero di modelli nella pagina
            ->with(['category', 'mainVersion.cover'])
            // Conta solo le versioni pubblicate: il pubblico non deve sapere quante bozze esistono
            ->withCount(['versions' => fn ($query) => $query->published()])
            ->orderBy('name')
            // 24 modelli per pagina: divisibile per 2, 3, 4 e 6, quindi la griglia del sito resta sempre piena
            ->paginate(24);

        return CarModelResource::collection($models);
    }
}
