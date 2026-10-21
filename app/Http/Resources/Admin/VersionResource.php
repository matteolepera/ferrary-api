<?php

namespace App\Http\Resources\Admin;

use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @mixin Version
 */
class VersionResource extends JsonResource
{
    /**
     * Campi con gli stessi nomi delle colonne: corrispondono uno a uno ai campi del form.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'car_model_id' => $this->car_model_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'vehicle_type' => $this->vehicle_type,
            'body_type' => $this->body_type,
            'project_code' => $this->project_code,
            'seats' => $this->seats,
            'year_start' => $this->year_start,
            'year_end' => $this->year_end,
            'in_production' => $this->in_production,
            'production_type' => $this->production_type,
            'units_produced' => $this->units_produced,
            'description' => $this->getTranslations('description'),
            'sort_order' => $this->sort_order,
            'status' => $this->publicationStatus(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Tutte le colonne delle tabelle collegate, tranne quelle tecniche
            'specs' => $this->whenLoaded('specs', fn () => $this->specs
                ? Arr::except($this->specs->attributesToArray(), ['version_id', 'created_at', 'updated_at'])
                : null),
            'powertrain' => $this->whenLoaded('powertrain', fn () => $this->powertrain
                ? Arr::except($this->powertrain->attributesToArray(), ['version_id', 'created_at', 'updated_at'])
                : null),
        ];
    }
}
