<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\User;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * I dati del pannello dell'area admin, in un'unica risposta.
 */
class DashboardController extends Controller
{
    /** Quante pubblicazioni in arrivo mostrare nel calendario */
    private const UPCOMING_LIMIT = 5;

    /** Quante schede da completare mostrare nel pannello */
    private const TO_COMPLETE_LIMIT = 12;

    /** Quante attività recenti mostrare nel pannello */
    private const ACTIVITY_LIMIT = 10;

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
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'log' => $activity->log_name,
                'action' => $this->action($activity),
                'causer' => $activity->causer?->getAttribute('name'),
                // "App\Models\CarModel" diventa "car_model"
                'subject_type' => Str::snake(class_basename($activity->subject_type ?? '')),
                'subject_name' => $this->subjectName($activity),
                'created_at' => $activity->created_at?->toIso8601String() ?? '',
            ])
            ->all();
    }

    /**
     * L'azione in parole semplici: per Spatie pubblicare e correggere un nome sono entrambi "updated".
     */
    private function action(Activity $activity): string
    {
        $changed = data_get($activity->attribute_changes, 'attributes', []);

        if ($activity->event === 'updated' && is_array($changed)) {
            if (array_key_exists('published_at', $changed)) {
                $date = $changed['published_at'];

                return match (true) {
                    $date === null => 'unpublished',
                    // Confrontata con il momento della modifica, non con adesso
                    Carbon::parse($date)->isAfter($activity->created_at) => 'scheduled',
                    default => 'published',
                };
            }

            if (array_key_exists('role', $changed)) {
                return 'role_changed';
            }
        }

        return $activity->event ?? 'updated';
    }

    /**
     * Il nome di ciò che è stato modificato. Se il record non esiste più,
     * lo recuperiamo dai valori salvati nel registro.
     */
    private function subjectName(Activity $activity): ?string
    {
        $name = $activity->subject?->getAttribute('name')
            ?? data_get($activity->attribute_changes, 'attributes.name')
            ?? data_get($activity->attribute_changes, 'old.name');

        return is_string($name) ? $name : null;
    }
}
