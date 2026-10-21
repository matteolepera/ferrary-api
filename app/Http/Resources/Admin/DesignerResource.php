<?php

namespace App\Http\Resources\Admin;

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
            'versions_count' => $this->whenCounted('versions'),
            // Presente solo quando il designer arriva da una versione
            'role' => $this->whenPivotLoaded('designer_version', fn () => data_get($this->resource, 'pivot.role')),
        ];
    }
}
