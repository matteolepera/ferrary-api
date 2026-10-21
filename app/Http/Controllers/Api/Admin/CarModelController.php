<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CarModelIndexRequest;
use App\Http\Resources\Admin\CarModelResource;
use App\Models\CarModel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CarModelController extends Controller
{
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

    public function show(CarModel $carModel): CarModelResource
    {
        Gate::authorize('view', $carModel);

        $carModel->load(['category', 'predecessor'])->loadCount('versions');

        return new CarModelResource($carModel);
    }
}
