<?php

namespace App\Http\Resources;

use App\Models\VersionRacing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VersionRacing
 */
class VersionRacingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'championship' => $this->championship,
            'season_start' => $this->season_start,
            'season_end' => $this->season_end,
            'races' => $this->races,
            'wins' => $this->wins,
            'poles' => $this->poles,
            'podiums' => $this->podiums,
            'fastest_laps' => $this->fastest_laps,
            'drivers_titles' => $this->drivers_titles,
            'constructors_titles' => $this->constructors_titles,
            // Note nella lingua corrente, grazie a HasTranslations
            'notes' => $this->notes,
        ];
    }
}
