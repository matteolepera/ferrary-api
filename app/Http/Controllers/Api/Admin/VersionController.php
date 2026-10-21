<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVersionRequest;
use App\Http\Requests\Admin\UpdateVersionRequest;
use App\Http\Resources\Admin\VersionResource;
use App\Models\CarModel;
use App\Models\Version;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

#[Group('Admin · Versioni', weight: 13)]
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

    /**
     * Dettaglio di una versione, con specifiche, motorizzazione, designer e piloti.
     */
    public function show(Version $version): VersionResource
    {
        Gate::authorize('view', $version);

        $version->load(['specs', 'powertrain', 'designers', 'drivers']);

        return new VersionResource($version);
    }

    /**
     * Crea una versione in bozza, con specifiche e motorizzazione in un’unica operazione.
     */
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
            $specs = array_filter($data['specs'] ?? [], fn($value) => $value !== null);
            if ($specs !== []) {
                $version->specs()->create($specs);
            }

            $powertrain = array_filter($data['powertrain'] ?? [], fn($value) => $value !== null);
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

    /**
     * 	Modifica una versione; le sezioni inviate sostituiscono quelle esistenti.
     */
    public function update(UpdateVersionRequest $request, Version $version): VersionResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($version, $data) {
            if (array_key_exists('description', $data)) {
                $version->replaceTranslations('description', array_filter($data['description'] ?? [], 'filled'));
            }

            $version->fill(Arr::except($data, ['description', 'specs', 'powertrain']))->save();

            if (array_key_exists('specs', $data)) {
                $this->replaceSection($version->specs(), $data['specs']);
            }

            if (array_key_exists('powertrain', $data)) {
                $this->replaceSection($version->powertrain(), $data['powertrain']);
            }
        });

        $version->load(['specs', 'powertrain', 'designers', 'drivers']);

        return new VersionResource($version);
    }

    /**
     * Sostituisce una sezione 1:1 con i valori ricevuti: i campi assenti diventano vuoti,
     * e una sezione senza valori viene eliminata.
     */
    private function replaceSection(HasOne $relation, ?array $values): void
    {
        $values = array_filter($values ?? [], fn($value) => $value !== null);

        if ($values === []) {
            // first()->delete() invece di una delete diretta: così il registro attività lo annota
            $relation->first()?->delete();

            return;
        }

        // Tutte le colonne a null, poi sovrascritte da quelle ricevute
        $columns = array_diff($relation->getRelated()->getFillable(), ['version_id']);

        $relation->updateOrCreate([], [...array_fill_keys($columns, null), ...$values]);
    }

    /**
     * Sposta la versione nel cestino.
     */
    public function destroy(Version $version): Response
    {
        Gate::authorize('delete', $version);

        $version->delete();

        return response()->noContent();
    }
}
