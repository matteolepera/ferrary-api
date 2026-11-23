<?php

namespace App\Http\Resources;

use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CarModel
 */
class CarModelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            // Copertina per l'elenco: quella della versione principale.
            // Compare solo se il controller ha caricato 'mainVersion'; vale null se la versione non ha copertina.
            'cover' => new ImageResource($this->whenLoaded('mainVersion', fn () => $this->mainVersion?->cover)),

            // I tre dati chiave della versione principale, per le card e il carosello.
            // Compaiono solo se il controller ha caricato specifiche e motorizzazione.
            'highlights' => $this->whenLoaded('mainVersion', function () {
                $version = $this->mainVersion;

                // Senza il caricamento anticipato, leggere specs e powertrain farebbe partire nuove query
                if ($version === null || ! $version->relationLoaded('specs') || ! $version->relationLoaded('powertrain')) {
                    return null;
                }

                $specs = $version->specs;
                $powertrain = $version->powertrain;

                return [
                    // La potenza di sistema delle ibride, altrimenti quella del motore termico
                    'power_cv' => $powertrain->system_power_cv ?? $powertrain?->ice_power_cv,
                    'zero_to_100_sec' => $specs?->zero_to_100_sec === null ? null : (float) $specs->zero_to_100_sec,
                    'top_speed_kmh' => $specs?->top_speed_kmh,
                    // I risultati in gara, sommati su tutti i campionati: per le auto da corsa
                    // dicono più della velocità. Null se la versione non ha mai corso
                    'racing' => $this->racingTotals($version),
                ];
            }),

            // Linea di discendenza: predecessore e successori, per navigare la storia dei modelli
            'predecessor' => new CarModelResource($this->whenLoaded('predecessor')),
            'successors' => CarModelResource::collection($this->whenLoaded('successors')),

            // L'elenco completo delle versioni, per il selettore in stile Amazon
            'versions' => VersionResource::collection($this->whenLoaded('versions')),

            // Solo il numero di versioni, senza caricarle: utile negli elenchi ("3 versioni").
            // Compare solo se il controller lo chiede con ->withCount('versions').
            'versions_count' => $this->whenCounted('versions'),

            // Anno della prima versione pubblicata, se calcolato dal controller con withMin
            'first_year' => $this->whenAggregated('versions', 'year_start', 'min'),
        ];
    }

    /**
     * Gare, vittorie e titoli di una versione, sommati su tutti i suoi campionati.
     * Gare e vittorie restano null se nessun campionato le indica, come nella scheda del modello;
     * i titoli sono quelli piloti più quelli costruttori.
     *
     * @return array{races: int|null, wins: int|null, titles: int}|null
     */
    private function racingTotals(Version $version): ?array
    {
        // Senza il caricamento anticipato, leggere i risultati farebbe partire una query per ogni modello
        if (! $version->relationLoaded('racingRecords') || $version->racingRecords->isEmpty()) {
            return null;
        }

        $records = $version->racingRecords;

        // La somma di un campo, o null se nessun campionato lo indica
        $total = fn (string $field): ?int => $records->whereNotNull($field)->isEmpty()
            ? null
            : (int) $records->sum($field);

        return [
            'races' => $total('races'),
            'wins' => $total('wins'),
            'titles' => (int) ($records->sum('drivers_titles') + $records->sum('constructors_titles')),
        ];
    }
}
