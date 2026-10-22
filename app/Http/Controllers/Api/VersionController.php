<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VersionResource;
use App\Models\CarModel;
use App\Models\Version;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;

#[Group('Pubblico · Versioni', weight: 2)]
class VersionController extends Controller
{
    /**
     * Scheda tecnica completa di una versione, cercata per slug.
     */
    public function show(Version $version): VersionResource
    {
        // Visibile solo se pubblicata e se lo è anche il suo modello
        abort_unless($version->isPublished(), 404);
        abort_unless(CarModel::visible()->whereKey($version->car_model_id)->exists(), 404);

        $version->load([
            'carModel.category',
            'cover',
            'images',
            'specs',
            'powertrain.engine',
            'racingRecords' => fn ($query) => $query->orderBy('season_start'),
            'designers',
            'drivers',
        ]);

        return new VersionResource($version);
    }

    /**
     * Visibile al pubblico: pubblicata e di un modello visibile.
     *
     * @param  Builder<Version>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->published()
            ->whereHas('carModel', fn ($query) => $query->visible());
    }
}
