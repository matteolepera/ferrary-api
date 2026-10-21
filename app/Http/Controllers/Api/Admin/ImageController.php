<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImageRequest;
use App\Http\Resources\Admin\ImageResource;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageController extends Controller
{
    public function index(Version $version): AnonymousResourceCollection
    {
        Gate::authorize('view', $version);

        return ImageResource::collection($version->images()->get());
    }

    public function store(StoreImageRequest $request, Version $version): JsonResponse
    {
        // store() genera un nome casuale e univoco: nessuna sovrascrittura, nessun nome pericoloso
        $path = $request->file('image')->store("versions/{$version->id}", 'public');

        try {
            $image = DB::transaction(function () use ($request, $version, $path) {
                // La prima immagine di una versione diventa la copertina in automatico
                $isCover = $request->boolean('is_cover') || ! $version->images()->exists();

                // Una sola copertina per versione
                if ($isCover) {
                    $version->images()->update(['is_cover' => false]);
                }

                return $version->images()->create([
                    'path' => $path,
                    'alt_text' => array_filter($request->validated('alt_text'), 'filled'),
                    'is_cover' => $isCover,
                    // In coda alle immagini esistenti
                    'sort_order' => (int) $version->images()->max('sort_order') + 1,
                ]);
            });
        } catch (Throwable $exception) {
            // Se il database fallisce, il file non deve restare orfano sul disco
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return (new ImageResource($image))
            ->response()
            ->setStatusCode(201);
    }
}
