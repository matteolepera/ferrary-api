<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CarModelIndexRequest;
use App\Http\Resources\CarModelResource;
use App\Models\CarModel;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoint pubblici dei modelli: accessibili senza login, mostrano solo contenuti pubblicati.
 */
#[Group('Pubblico · Modelli', weight: 1)]
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
        $models = CarModel::query()
            ->visible()
            ->filter($request->validated())
            ->sorted($request->validated('sort'))
            ->with(['category', 'mainVersion.cover'])
            ->withCount(['versions' => fn ($query) => $query->published()])
            ->withMin(['versions' => fn ($query) => $query->published()], 'year_start')
            ->paginate(24)
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
