<?php

namespace App\Http\Resources;

use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Version
 */
class VersionResource extends JsonResource
{
    /**
     * Una sola Resource per due usi: negli elenchi carica pochi dati,
     * nella scheda completa il controller carica tutte le relazioni.
     * Grazie a whenLoaded, ogni sezione compare solo se è stata caricata.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            // Enum convertito in testo nel JSON (es. "stradale", "f1")
            'vehicle_type' => $this->vehicle_type,
            'body_type' => $this->body_type,
            'project_code' => $this->project_code,
            'seats' => $this->seats,
            'years' => [
                'start' => $this->year_start,
                'end' => $this->year_end,
                'in_production' => $this->in_production,
            ],
            'production' => [
                'type' => $this->production_type,
                'units' => $this->units_produced,
            ],
            'description' => $this->description,

            // Il modello di appartenenza: utile nella scheda della versione per tornare alla famiglia
            'model' => new CarModelResource($this->whenLoaded('carModel')),

            // Copertina da sola (per elenchi e anteprime) e galleria completa (per la scheda)
            'cover' => new ImageResource($this->whenLoaded('cover')),
            'images' => ImageResource::collection($this->whenLoaded('images')),

            // Le sezioni della scheda tecnica, ciascuna con la sua Resource
            'specs' => new VersionSpecResource($this->whenLoaded('specs')),
            'powertrain' => new VersionPowertrainResource($this->whenLoaded('powertrain')),
            'racing' => VersionRacingResource::collection($this->whenLoaded('racingRecords')),
            'designers' => DesignerResource::collection($this->whenLoaded('designers')),
            'drivers' => DriverResource::collection($this->whenLoaded('drivers')),
        ];
    }
}
