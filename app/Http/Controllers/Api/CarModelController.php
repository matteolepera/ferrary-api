<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CarModelIndexRequest;
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
    /**
     * Elenco paginato dei modelli visibili, con filtri opzionali.
     */
    public function index(CarModelIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $models = CarModel::query()
            ->visible()
            ->when($filters['category'] ?? null, fn ($query, string $slug) => $query
                ->whereRelation('category', 'slug', $slug))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query
                // Le parentesi raggruppano le due condizioni in OR, senza toccare quelle di visible()
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('versions', fn ($query) => $query
                        ->published()
                        ->where('name', 'like', "%{$search}%"))))
            ->with(['category', 'mainVersion.cover'])
            ->withCount(['versions' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->paginate(24)
            // Mantiene i filtri nei link delle altre pagine
            ->withQueryString();

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
