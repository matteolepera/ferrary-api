<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Grazie a HasTranslations, 'name' è già il testo nella lingua corrente (it o en)
            'name' => $this->name,
            'slug' => $this->slug,
            // Numero di modelli visibili, solo se il controller l'ha calcolato con withCount
            'models_count' => $this->whenCounted('carModels'),
        ];
    }
}
