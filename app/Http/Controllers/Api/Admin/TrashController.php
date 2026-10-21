<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CarModelResource;
use App\Http\Resources\Admin\VersionResource;
use App\Models\CarModel;
use App\Models\Image;
use App\Models\Version;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Contenuto del cestino e ripristino.
 */
#[Group('Admin · Cestino', weight: 18)]
class TrashController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewTrash');

        $models = CarModel::onlyTrashed()
            ->with('category')
            ->latest('deleted_at')
            ->get();

        // Solo le versioni cestinate da sole: whereHas esclude quelle con il modello nel cestino
        $versions = Version::onlyTrashed()
            ->whereHas('carModel')
            ->with('carModel')
            ->latest('deleted_at')
            ->get();

        return response()->json([
            'models' => CarModelResource::collection($models),
            'versions' => VersionResource::collection($versions),
        ]);
    }

    /**
     * Ripristina il modello, insieme alle versioni cestinate con lui.
     */
    public function restoreModel(CarModel $carModel): CarModelResource
    {
        Gate::authorize('restore', $carModel);

        abort_unless($carModel->trashed(), 409, 'Il modello non è nel cestino.');

        $carModel->restore();

        return new CarModelResource($carModel);
    }

    public function restoreVersion(Version $version): VersionResource
    {
        Gate::authorize('restore', $version);

        abort_unless($version->trashed(), 409, 'La versione non è nel cestino.');

        // Ripristinarla da sola la lascerebbe senza un modello visibile
        abort_if(
            CarModel::onlyTrashed()->whereKey($version->car_model_id)->exists(),
            409,
            'Il modello di questa versione è nel cestino: ripristina prima il modello.',
        );

        $version->restore();

        return new VersionResource($version);
    }

    /**
     * Elimina definitivamente un modello nel cestino, con tutte le sue versioni.
     */
    public function forceDeleteModel(CarModel $carModel): Response
    {
        Gate::authorize('forceDelete', $carModel);

        abort_unless($carModel->trashed(), 409, 'Si possono eliminare definitivamente solo i modelli nel cestino.');

        DB::transaction(function () use ($carModel) {
            // Tutte le versioni, anche quelle cestinate in precedenza
            Version::withTrashed()
                ->where('car_model_id', $carModel->id)
                ->get()
                ->each(fn (Version $version) => $this->purgeVersion($version));

            $carModel->forceDelete();
        });

        return response()->noContent();
    }

    /**
     * Elimina definitivamente una versione nel cestino.
     */
    public function forceDeleteVersion(Version $version): Response
    {
        Gate::authorize('forceDelete', $version);

        abort_unless($version->trashed(), 409, 'Si possono eliminare definitivamente solo le versioni nel cestino.');

        DB::transaction(fn () => $this->purgeVersion($version));

        return response()->noContent();
    }

    /**
     * Le immagini passano da Eloquent, così i loro file vengono cancellati;
     * specifiche, motorizzazione, dati sportivi e collegamenti li elimina il database con le cascate.
     */
    private function purgeVersion(Version $version): void
    {
        $version->images()->get()->each(fn (Image $image) => $image->delete());

        $version->forceDelete();
    }
}
