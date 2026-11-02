<?php

namespace App\Http\Resources\Admin;

use App\Support\ActivityPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * Una voce del registro: la forma compatta del pannello, più chi, su cosa, e le modifiche.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Activity $activity */
        $activity = $this->resource;
        $presenter = app(ActivityPresenter::class);

        return [
            ...$presenter->summary($activity),
            'causer_id' => $activity->causer_id,
            'subject_id' => $activity->subject_id,
            'changes' => $presenter->changes($activity),
        ];
    }
}
