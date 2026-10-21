<?php

namespace App\Http\Resources\Admin;

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
            'description' => $this->getTranslations('description'),
            // L'id serve al form, l'oggetto alla visualizzazione
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'predecessor_id' => $this->predecessor_id,
            'predecessor' => new CarModelResource($this->whenLoaded('predecessor')),
            'versions_count' => $this->whenCounted('versions'),
            'status' => $this->publicationStatus(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
