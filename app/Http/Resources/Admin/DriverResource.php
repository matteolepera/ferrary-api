<?php

namespace App\Http\Resources\Admin;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Driver
 */
class DriverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'slug' => $this->slug,
            'nationality' => $this->nationality,
            'birth_date' => $this->birth_date?->toDateString(),
            'versions_count' => $this->whenCounted('versions'),
        ];
    }
}
