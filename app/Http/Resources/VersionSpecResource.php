<?php

namespace App\Http\Resources;

use App\Models\VersionSpec;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VersionSpec
 */
class VersionSpecResource extends JsonResource
{
    /**
     * Le colonne sono raggruppate per argomento: il frontend può mostrare
     * ogni gruppo come una sezione della scheda tecnica.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'dimensions' => [
                'length_mm' => $this->length_mm,
                'width_mm' => $this->width_mm,
                'height_mm' => $this->height_mm,
                'wheelbase_mm' => $this->wheelbase_mm,
            ],
            'weight' => [
                'dry_kg' => $this->dry_weight_kg,
                'front_distribution_pct' => $this->weight_front_pct,
            ],
            'chassis' => [
                'material' => $this->chassis_material,
                'brakes' => $this->brakes,
            ],
            'performance' => [
                'top_speed_kmh' => $this->top_speed_kmh,
                'zero_to_100_sec' => $this->zero_to_100_sec,
                'zero_to_200_sec' => $this->zero_to_200_sec,
                'fiorano_lap_sec' => $this->fiorano_lap_sec,
            ],
        ];
    }
}
