<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVersionRequest;
use App\Http\Resources\Admin\VersionResource;
use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VersionController extends Controller
{
    /**
     * Tutte le versioni di un modello, bozze comprese, nell'ordine del selettore.
     */
    public function index(CarModel $carModel): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Version::class);

        return VersionResource::collection($carModel->versions()->get());
    }

    public function show(Version $version): VersionResource
    {
        Gate::authorize('view', $version);

        $version->load(['specs', 'powertrain']);

        return new VersionResource($version);
    }

    public function store(StoreVersionRequest $request, CarModel $carModel): JsonResponse
    {
        $data = $request->validated();

        // Tutto o niente: se una delle tre scritture fallisce, nessuna resta salvata
        $version = DB::transaction(function () use ($carModel, $data) {
            $version = $carModel->versions()->create([
                ...Arr::except($data, ['specs', 'powertrain', 'description']),
                'description' => array_filter($data['description'] ?? [], 'filled'),
            ]);

            // Le sezioni facoltative si salvano solo se contengono almeno un valore
            $specs = array_filter($data['specs'] ?? [], fn ($value) => $value !== null);
            if ($specs !== []) {
                $version->specs()->create($specs);
            }

            $powertrain = array_filter($data['powertrain'] ?? [], fn ($value) => $value !== null);
            if ($powertrain !== []) {
                $version->powertrain()->create($powertrain);
            }

            return $version;
        });

        $version->load(['specs', 'powertrain']);

        return (new VersionResource($version))
            ->response()
            ->setStatusCode(201);
    }
}
