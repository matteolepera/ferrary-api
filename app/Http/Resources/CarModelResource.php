<?php

namespace App\Http\Resources;

use App\Models\CarModel;
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
}
