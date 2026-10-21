<?php

namespace App\Http\Resources;

use App\Models\Engine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Engine
 */
class EngineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'architecture' => $this->architecture,
            'v_angle_deg' => $this->v_angle_deg,
            'cylinders' => $this->cylinders,
            'displacement_cc' => $this->displacement_cc,
            'bore_mm' => $this->bore_mm,
            'stroke_mm' => $this->stroke_mm,
            'valves_per_cylinder' => $this->valves_per_cylinder,
            'aspiration' => $this->aspiration,
            'description' => $this->description,
            'versions' => VersionResource::collection($this->whenLoaded('versions')),
            'versions_count' => $this->whenCounted('versions'),
        ];
    }
}
