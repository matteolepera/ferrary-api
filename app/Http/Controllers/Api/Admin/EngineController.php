<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EngineRequest;
use App\Http\Resources\Admin\EngineResource;
use App\Models\Engine;
use App\Models\VersionPowertrain;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Cataloghi', weight: 16)]
class EngineController extends Controller
{
    /**
     * 	Elenco dei motori, con il numero di versioni che li montano.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Engine::class);

        return EngineResource::collection(
            Engine::query()->withCount('versions')->orderBy('code')->get(),
        );
    }

    /**
     * 	Dettaglio di un motore.
     */
    public function show(Engine $engine): EngineResource
    {
        Gate::authorize('view', $engine);

        return new EngineResource($engine->loadCount('versions'));
    }

    /**
     * Crea un motore.
     */
    public function store(EngineRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['description'] = array_filter($data['description'] ?? [], 'filled');

        $engine = Engine::create($data);

        return (new EngineResource($engine))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Sostituisce il motore con i dati ricevuti: i campi assenti diventano vuoti.
     */
    public function update(EngineRequest $request, Engine $engine): EngineResource
    {
        $data = $request->validated();

        $engine->replaceTranslations('description', array_filter($data['description'] ?? [], 'filled'));

        $columns = array_diff($engine->getFillable(), ['description']);
        $engine->fill([...array_fill_keys($columns, null), ...Arr::except($data, ['description'])])->save();

        return new EngineResource($engine);
    }

    /**
     * 	Elimina un motore inutilizzato; risponde 409 se è usato da una versione.
     */
    public function destroy(Engine $engine): Response
    {
        Gate::authorize('delete', $engine);

        // Query diretta sulle motorizzazioni: conta anche le versioni nel cestino
        abort_if(
            VersionPowertrain::where('engine_id', $engine->id)->exists(),
            409,
            'Il motore è usato da almeno una versione: scollegalo prima di eliminarlo.',
        );

        $engine->delete();

        return response()->noContent();
    }
}
