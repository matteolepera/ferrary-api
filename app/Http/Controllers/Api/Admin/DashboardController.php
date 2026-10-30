<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * I dati del pannello dell'area admin, in un'unica risposta.
 */
class DashboardController extends Controller
{
    /** Quante pubblicazioni in arrivo mostrare nel calendario */
    private const UPCOMING_LIMIT = 5;

    /** Quante schede da completare mostrare nel pannello */
    private const TO_COMPLETE_LIMIT = 12;

    public function __invoke(): JsonResponse
    {
        // Lo stesso permesso dell'elenco dei modelli: lo ha tutto lo staff
        Gate::authorize('viewAny', CarModel::class);

        return response()->json([
            'data' => [
                'counts' => [
                    'published' => CarModel::published()->count(),
                    'drafts' => CarModel::status(PublicationStatus::Draft)->count(),
                    'scheduled' => CarModel::status(PublicationStatus::Scheduled)->count(),
                    'trashed' => CarModel::onlyTrashed()->count(),
                ],
                'scheduled' => $this->upcoming(),
                'to_complete' => $this->toComplete(),
            ],
        ]);
    }

    /**
     * Le prossime pubblicazioni, modelli e versioni insieme, dalla più vicina.
     *
     * @return list<array{type: string, id: int, name: string, model: string|null, published_at: string}>
     */
    private function upcoming(): array
    {
        $models = CarModel::status(PublicationStatus::Scheduled)
            ->orderBy('published_at')
            ->limit(self::UPCOMING_LIMIT)
            ->get()
            ->map(fn (CarModel $model) => [
                'type' => 'model',
                'id' => $model->id,
                'name' => $model->name,
                'model' => null,
                'published_at' => $model->published_at->toIso8601String(),
            ]);

        $versions = Version::status(PublicationStatus::Scheduled)
            // Solo le versioni di modelli non cestinati, con il nome del modello in un'unica query
            ->whereHas('carModel')
            ->with('carModel')
            ->orderBy('published_at')
            ->limit(self::UPCOMING_LIMIT)
            ->get()
            ->map(fn (Version $version) => [
                'type' => 'version',
                'id' => $version->id,
                'name' => $version->name,
                'model' => $version->carModel->name,
                'published_at' => $version->published_at->toIso8601String(),
            ]);

        return $models->concat($versions)
            ->sortBy('published_at')
            ->take(self::UPCOMING_LIMIT)
            ->values()
            ->all();
    }

    /**
     * Le versioni a cui manca qualcosa, dalle ultime modificate: sono quelle su cui si sta lavorando.
     *
     * @return list<array{id: int, name: string, model: string, model_id: int, cover: string|null, issues: list<string>, updated_at: string}>
     */
    private function toComplete(): array
    {
        return Version::query()
            // Solo versioni di modelli non cestinati
            ->whereHas('carModel')
            // Almeno una mancanza; il raggruppamento tiene insieme le tre condizioni in "o"
            ->where(fn ($query) => $query
                ->status(PublicationStatus::Draft)
                ->orWhereDoesntHave('images')
                ->orWhereDoesntHave('specs'))
            ->with(['carModel', 'cover'])
            // Due colonne in più, vere o false, calcolate dal database nella stessa query
            ->withExists(['images', 'specs'])
            ->latest('updated_at')
            ->limit(self::TO_COMPLETE_LIMIT)
            ->get()
            ->map(fn (Version $version) => [
                'id' => $version->id,
                'name' => $version->name,
                'model' => $version->carModel->name,
                'model_id' => $version->car_model_id,
                'cover' => $version->cover ? Storage::disk('public')->url($version->cover->path) : null,
                'issues' => array_values(array_filter([
                    $version->published_at === null ? 'draft' : null,
                    $version->getAttribute('images_exists') ? null : 'no_photos',
                    $version->getAttribute('specs_exists') ? null : 'no_specs',
                ])),
                'updated_at' => $version->updated_at?->toIso8601String() ?? '',
            ])
            ->all();
    }
}
