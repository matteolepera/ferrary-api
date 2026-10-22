<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarModelIndexRequest;
use App\Http\Requests\Admin\StoreCarModelRequest;
use App\Http\Requests\Admin\UpdateCarModelRequest;
use App\Http\Resources\Admin\CarModelResource;
use App\Models\CarModel;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Modelli', weight: 12)]
class CarModelController extends Controller
{
    /**
     * Elenco dei modelli, bozze comprese, con filtri per nome, stato e categoria.
     */
    public function index(CarModelIndexRequest $request): AnonymousResourceCollection
    {
        $models = CarModel::query()
            ->when($request->validated('search'), fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%"))
            // enum() converte il parametro nell'enum, oppure restituisce null se manca
            ->when($request->enum('status', PublicationStatus::class), fn ($query, $status) => $query
                ->status($status))
            ->when($request->validated('category_id'), fn ($query, $categoryId) => $query
                ->where('category_id', $categoryId))
            ->with('category')
            ->withCount('versions')
            // Prima le schede modificate più di recente
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return CarModelResource::collection($models);
    }

    /**
     * Dettaglio di un modello, con categoria e predecessore.
     */
    public function show(CarModel $carModel): CarModelResource
    {
        Gate::authorize('view', $carModel);

        $carModel->load(['category', 'predecessor'])->loadCount('versions');

        return new CarModelResource($carModel);
    }

    /**
     * Crea un modello in bozza.
     */
    public function store(StoreCarModelRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['description'] = array_filter($data['description'], 'filled');

        // Nasce in bozza: published_at non è nel $fillable
        $carModel = CarModel::create($data);

        $carModel->load(['category', 'predecessor'])->loadCount('versions');

        return (new CarModelResource($carModel))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Modifica un modello, con il controllo dei cicli nel predecessore.
     */
    public function update(UpdateCarModelRequest $request, CarModel $carModel): CarModelResource
    {
        $data = $request->validated();

        if (array_key_exists('description', $data)) {
            $carModel->replaceTranslations('description', array_filter($data['description'], 'filled'));
            unset($data['description']);
        }

        $carModel->fill($data)->save();

        $carModel->load(['category', 'predecessor'])->loadCount('versions');

        return new CarModelResource($carModel);
    }

    /**
     * Sposta il modello nel cestino, insieme alle sue versioni.
     */
    public function destroy(CarModel $carModel): Response
    {
        Gate::authorize('delete', $carModel);

        $carModel->delete();

        return response()->noContent();
    }
}
