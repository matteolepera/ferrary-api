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
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Driver::class);

        return DriverResource::collection(
            Driver::query()->withCount('versions')->orderBy('last_name')->orderBy('first_name')->get(),
        );
    }

    public function show(Driver $driver): DriverResource
    {
        Gate::authorize('view', $driver);

        return new DriverResource($driver->loadCount('versions'));
    }

    public function store(DriverRequest $request): JsonResponse
    {
        $driver = Driver::create($request->validated());

        return (new DriverResource($driver))
            ->response()
            ->setStatusCode(201);
    }

    public function update(DriverRequest $request, Driver $driver): DriverResource
    {
        $driver->update($request->validated());

        return new DriverResource($driver);
    }

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
