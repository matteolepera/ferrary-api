<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EngineResource;
use App\Models\Engine;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EngineController extends Controller
{
    /**
     * Motori con almeno una versione visibile.
     */
    public function index(): AnonymousResourceCollection
    {
        $engines = Engine::query()
            ->whereHas('versions', fn ($query) => $query->visible())
            ->withCount(['versions' => fn ($query) => $query->visible()])
            ->orderBy('code')
            ->get();

        return EngineResource::collection($engines);
    }

    /**
     * Un motore con tutte le versioni visibili che lo montano.
     */
    public function show(Engine $engine): EngineResource
    {
        $engine->load([
            'versions' => fn ($query) => $query
                ->visible()
                ->with(['carModel', 'cover'])
                ->orderBy('year_start'),
        ]);

        // Un motore senza versioni visibili non ha una pagina pubblica
        abort_if($engine->versions->isEmpty(), 404);

        return new EngineResource($engine);
    }
}
