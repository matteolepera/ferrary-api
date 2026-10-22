<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Pubblico · Cataloghi', weight: 3)]
class DriverController extends Controller
{
    /**
     * Piloti con almeno una versione visibile.
     */
    public function index(): AnonymousResourceCollection
    {
        $drivers = Driver::query()
            ->whereHas('versions', fn ($query) => $query->visible())
            ->withCount(['versions' => fn ($query) => $query->visible()])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return DriverResource::collection($drivers);
    }

    /**
     * Un pilota con le versioni visibili che ha guidato e le loro statistiche.
     */
    public function show(Driver $driver): DriverResource
    {
        $driver->load([
            'versions' => fn ($query) => $query
                ->visible()
                ->with(['carModel', 'cover', 'racingRecords'])
                ->orderBy('year_start'),
        ]);

        abort_if($driver->versions->isEmpty(), 404);

        return new DriverResource($driver);
    }
}
