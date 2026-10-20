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
            ->visible()
            ->with(['category', 'mainVersion.cover'])
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

    /**
     * Dettaglio pubblico di un modello, cercato per slug.
     */
    public function show(CarModel $carModel): CarModelResource
    {
        // 404 e non 403: non riveliamo che una bozza esiste
        abort_unless($carModel->isPublished(), 404);

        $carModel->load([
            'category',
            'versions' => fn ($query) => $query->published()->with('cover'),
            // Solo modelli visibili: un link a una bozza porterebbe a un 404
            'predecessor' => fn ($query) => $query->visible(),
            'successors' => fn ($query) => $query->visible()->orderBy('name'),
        ]);

        // Niente schede vuote
        abort_if($carModel->versions->isEmpty(), 404);

        return new CarModelResource($carModel);
    }
}
