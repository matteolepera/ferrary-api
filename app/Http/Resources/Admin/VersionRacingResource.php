<?php

namespace App\Http\Resources\Admin;

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
            'id' => $this->id,
            'version_id' => $this->version_id,
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
            'notes' => $this->getTranslations('notes'),
        ];
    }
}
