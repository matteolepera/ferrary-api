<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CarModelResource;
use App\Http\Resources\Admin\VersionResource;
use App\Models\CarModel;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Pubblicazione, programmazione e ritiro di modelli e versioni.
 */
class PublicationController extends Controller
{
    public function publishModel(Request $request, CarModel $carModel): CarModelResource
    {
        $this->publish($request, $carModel);

        return new CarModelResource($carModel);
    }

    public function unpublishModel(CarModel $carModel): CarModelResource
    {
        $this->unpublish($carModel);

        return new CarModelResource($carModel);
    }

    /**
     * Pubblica subito, oppure alla data indicata in published_at.
     */
    private function publish(Request $request, CarModel|Version $record): void
    {
        Gate::authorize('publish', $record);

        $request->validate(['published_at' => ['nullable', 'date']]);

        $record->published_at = $request->date('published_at') ?? now();
        $record->save();
    }

    /**
     * Riporta il record in bozza.
     */
    private function unpublish(CarModel|Version $record): void
    {
        Gate::authorize('publish', $record);

        $record->published_at = null;
        $record->save();
    }

    public function publishVersion(Request $request, Version $version): VersionResource
    {
        $this->publish($request, $version);

        return new VersionResource($version);
    }

    public function unpublishVersion(Version $version): VersionResource
    {
        $this->unpublish($version);

        return new VersionResource($version);
    }
}
