<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignerResource;
use App\Models\Designer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DesignerController extends Controller
{
    /**
     * Designer con almeno una versione visibile.
     */
    public function index(): AnonymousResourceCollection
    {
        $designers = Designer::query()
            ->whereHas('versions', fn ($query) => $query->visible())
            ->withCount(['versions' => fn ($query) => $query->visible()])
            ->orderBy('name')
            ->get();

        return DesignerResource::collection($designers);
    }

    /**
     * Un designer con tutte le versioni visibili a cui ha lavorato.
     */
    public function show(Designer $designer): DesignerResource
    {
        $designer->load([
            'versions' => fn ($query) => $query
                ->visible()
                ->with(['carModel', 'cover'])
                ->orderBy('year_start'),
        ]);

        abort_if($designer->versions->isEmpty(), 404);

        return new DesignerResource($designer);
    }
}
