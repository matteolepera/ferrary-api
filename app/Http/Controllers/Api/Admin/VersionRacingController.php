<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RacingRecordRequest;
use App\Http\Resources\Admin\VersionRacingResource;
use App\Models\Version;
use App\Models\VersionRacing;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Dati sportivi', weight: 14)]
class VersionRacingController extends Controller
{
    /**
     * 	Dati sportivi di una versione, in ordine di stagione.
     */
    public function index(Version $version): AnonymousResourceCollection
    {
        Gate::authorize('view', $version);

        return VersionRacingResource::collection(
            $version->racingRecords()->orderBy('season_start')->get(),
        );
    }

    /**
     * Aggiunge i dati di un campionato a una versione.
     */
    public function store(RacingRecordRequest $request, Version $version): JsonResponse
    {
        $data = $request->validated();
        $data['notes'] = array_filter($data['notes'] ?? [], 'filled');

        $racing = $version->racingRecords()->create($data);

        return (new VersionRacingResource($racing))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Sostituisce il record con i dati ricevuti: i campi assenti diventano vuoti.
     */
    public function update(RacingRecordRequest $request, VersionRacing $racing): VersionRacingResource
    {
        $data = $request->validated();

        $racing->replaceTranslations('notes', array_filter($data['notes'] ?? [], 'filled'));

        $columns = array_diff($racing->getFillable(), ['version_id', 'notes']);
        $racing->fill([...array_fill_keys($columns, null), ...Arr::except($data, ['notes'])])->save();

        return new VersionRacingResource($racing);
    }

    /**
     * Elimina i dati di un campionato.
     */
    public function destroy(VersionRacing $racing): Response
    {
        // Eliminare un record sportivo equivale a modificare la versione
        Gate::authorize('update', $racing->version);

        $racing->delete();

        return response()->noContent();
    }
}
