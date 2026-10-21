<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverRequest;
use App\Http\Resources\Admin\DriverResource;
use App\Models\Driver;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Cataloghi', weight: 16)]
class DriverController extends Controller
{
    /**
     * Elenco dei piloti, ordinato per cognome.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Driver::class);

        return DriverResource::collection(
            Driver::query()->withCount('versions')->orderBy('last_name')->orderBy('first_name')->get(),
        );
    }

    /**
     * Dettaglio di un pilota.
     */
    public function show(Driver $driver): DriverResource
    {
        Gate::authorize('view', $driver);

        return new DriverResource($driver->loadCount('versions'));
    }

    /**
     * 	Crea un pilota, generando lo slug da nome e cognome se manca.
     */
    public function store(DriverRequest $request): JsonResponse
    {
        $driver = Driver::create($request->validated());

        return (new DriverResource($driver))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 	Modifica un pilota.
     */
    public function update(DriverRequest $request, Driver $driver): DriverResource
    {
        $driver->update($request->validated());

        return new DriverResource($driver);
    }

    /**
     * 	Elimina un pilota non collegato; risponde 409 se ha delle versioni.
     */
    public function destroy(Driver $driver): Response
    {
        Gate::authorize('delete', $driver);

        // Come per i designer: niente collegamenti tolti in silenzio
        abort_if(
            $driver->versions()->withTrashed()->exists(),
            409,
            'Il pilota è collegato ad almeno una versione: scollegalo prima di eliminarlo.',
        );

        $driver->delete();

        return response()->noContent();
    }
}
