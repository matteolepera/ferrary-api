<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use App\Support\ActivityPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

/**
 * I dati del pannello dell'area admin, in un'unica risposta.
 */
class DashboardController extends Controller
{
    /** Quante pubblicazioni in arrivo mostrare nel calendario */
    private const UPCOMING_LIMIT = 5;

    /** Quante schede da completare mandare per ogni filtro: il pannello ne mostra tante così */
    private const TO_COMPLETE_LIMIT = 6;

    /** Le mancanze di una scheda, come le chiamano il pannello e i suoi filtri */
    private const ISSUES = ['draft', 'no_photos', 'no_specs'];

    /** Quante attività recenti mostrare nel pannello */
    private const ACTIVITY_LIMIT = 10;

    /**
     * Le frasi del registro le costruisce ActivityPresenter, la stessa classe del registro delle attività.
     */
    public function __construct(private ActivityPresenter $presenter) {}

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
                'to_complete_counts' => $this->toCompleteCounts(),
                'activity' => $this->activity(),
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
     * Le versioni a cui manca qualcosa: tutte, o solo quelle con la mancanza indicata.
     * Solo versioni di modelli non cestinati.
     *
     * @return Builder<Version>
     */
    private function incomplete(?string $issue = null): Builder
    {
        $query = Version::query()->whereHas('carModel');

        return match ($issue) {
            'draft' => $query->status(PublicationStatus::Draft),
            'no_photos' => $query->whereDoesntHave('images'),
            'no_specs' => $query->whereDoesntHave('specs'),
            // Almeno una mancanza; il raggruppamento tiene insieme le tre condizioni in "o"
            default => $query->where(fn ($query) => $query
                ->status(PublicationStatus::Draft)
                ->orWhereDoesntHave('images')
                ->orWhereDoesntHave('specs')),
        };
    }

    /**
     * Le versioni da completare, dalle ultime modificate: sono quelle su cui si sta lavorando.
     * Per ogni filtro del pannello arrivano le sue più recenti, insieme e senza doppioni:
     * così "Bozze" mostra le bozze anche se le ultime schede modificate sono altre.
     *
     * @return list<array{id: int, name: string, model: string, model_id: int, cover: string|null, issues: list<string>, updated_at: string}>
     */
    private function toComplete(): array
    {
        return collect([null, ...self::ISSUES])
            ->flatMap(fn (?string $issue) => $this->incomplete($issue)
                ->with(['carModel', 'cover'])
                // Due colonne in più, vere o false, calcolate dal database nella stessa query
                ->withExists(['images', 'specs'])
                ->latest('updated_at')
                ->limit(self::TO_COMPLETE_LIMIT)
                ->get())
            ->unique('id')
            ->sortByDesc('updated_at')
            ->values()
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

    /**
     * Quante versioni hanno ogni mancanza, contate nel database: i numeri dei filtri del pannello.
     *
     * @return array{all: int, draft: int, no_photos: int, no_specs: int}
     */
    private function toCompleteCounts(): array
    {
        return [
            'all' => $this->incomplete()->count(),
            'draft' => $this->incomplete('draft')->count(),
            'no_photos' => $this->incomplete('no_photos')->count(),
            'no_specs' => $this->incomplete('no_specs')->count(),
        ];
    }

    /**
     * Le ultime attività dello staff. Il log delle persone lo vede solo chi può gestire lo staff.
     *
     * @return list<array{id: int, log: string|null, action: string, causer: mixed, subject_type: string, subject_name: string|null, created_at: string}>
     */
    private function activity(): array
    {
        $logs = Gate::allows('viewAny', User::class) ? ['catalogo', 'staff'] : ['catalogo'];

        return Activity::query()
            ->whereIn('log_name', $logs)
            // Chi e su cosa, per tutte le voci in poche query
            ->with(['causer', 'subject'])
            // Per id e non per data: due modifiche nello stesso secondo restano nell'ordine giusto
            ->latest('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Activity $activity) => $this->presenter->summary($activity))
            ->all();
    }
}
