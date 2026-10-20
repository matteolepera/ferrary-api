<?php

namespace App\Http\Resources;

use App\Models\Designer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Designer
 */
class DesignerResource extends JsonResource
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
            'type' => $this->type,
            'country' => $this->country,

            // Il ruolo (design o carrozzeria) sta nella tabella ponte designer_version.
            // whenPivotLoaded lo aggiunge solo quando il designer arriva da una versione:
            // nella pagina di un singolo designer questo campo semplicemente non compare.
            'role' => $this->whenPivotLoaded('designer_version', fn () => data_get($this->resource, 'pivot.role')),
        ];
    }
}
