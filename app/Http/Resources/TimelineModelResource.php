<?php

namespace App\Http\Resources;

use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un modello nella linea del tempo della storia: solo quello che serve a una targhetta.
 *
 * @property CarModel $resource
 */
class TimelineModelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        return [
            'id' => $model->id,
            'slug' => $model->slug,
            'name' => $model->name,
            // L'anno della prima versione pubblicata: una colonna calcolata da withMin, non della tabella
            'first_year' => $model->getAttribute('versions_min_year_start'),
            'category' => $this->whenLoaded('category', fn () => [
                'slug' => $model->category->getAttribute('slug'),
                'name' => $model->category->getAttribute('name'),
            ]),
        ];
    }
}
