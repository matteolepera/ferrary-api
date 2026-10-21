<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DesignerRequest;
use App\Http\Resources\Admin\DesignerResource;
use App\Models\Designer;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Cataloghi', weight: 16)]
class DesignerController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Designer::class);

        return DesignerResource::collection(
            Designer::query()->withCount('versions')->orderBy('name')->get(),
        );
    }

    public function show(Designer $designer): DesignerResource
    {
        Gate::authorize('view', $designer);

        return new DesignerResource($designer->loadCount('versions'));
    }

    public function store(DesignerRequest $request): JsonResponse
    {
        $designer = Designer::create($request->validated());

        return (new DesignerResource($designer))
            ->response()
            ->setStatusCode(201);
    }

    public function update(DesignerRequest $request, Designer $designer): DesignerResource
    {
        $designer->update($request->validated());

        return new DesignerResource($designer);
    }

    public function destroy(Designer $designer): Response
    {
        Gate::authorize('delete', $designer);

        // Il database cancellerebbe i collegamenti da solo, ma toglieremmo i crediti
        // alle versioni senza che lo staff se ne accorga: meglio chiedere di scollegare prima
        abort_if(
            $designer->versions()->withTrashed()->exists(),
            409,
            'Il designer è collegato ad almeno una versione: scollegalo prima di eliminarlo.',
        );

        $designer->delete();

        return response()->noContent();
    }
}
