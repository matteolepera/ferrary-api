<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\VersionResource;
use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
}
