<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreImageRequest;
use App\Http\Requests\Admin\UpdateImageRequest;
use App\Http\Resources\Admin\ImageResource;
use App\Models\Image;
use App\Models\Version;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

#[Group('Admin · Immagini', weight: 15)]
class ImageController extends Controller
{
    /**
     * 	Immagini di una versione, nell’ordine della galleria.
     */
    public function index(Version $version): AnonymousResourceCollection
    {
        Gate::authorize('view', $version);

        return ImageResource::collection($version->images()->get());
    }

    /**
     * 	Carica un’immagine; la prima di una versione diventa la copertina.
     */
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

    /**
     * 	Modifica il testo alternativo o imposta l’immagine come copertina.
     */
    public function update(UpdateImageRequest $request, Image $image): ImageResource
    {
        DB::transaction(function () use ($request, $image) {
            if ($request->has('alt_text')) {
                $image->replaceTranslations('alt_text', array_filter($request->validated('alt_text'), 'filled'));
            }

            if ($request->boolean('is_cover')) {
                // Una sola copertina per versione: toglie il segno alle altre
                Image::where('version_id', $image->version_id)
                    ->whereKeyNot($image->id)
                    ->update(['is_cover' => false]);

                $image->is_cover = true;
            }

            $image->save();
        });

        return new ImageResource($image);
    }

    /**
     * Riordina le immagini della versione secondo l'elenco di id ricevuto.
     */
    public function reorder(Request $request, Version $version): AnonymousResourceCollection
    {
        Gate::authorize('update', $version);

        $ids = $version->images()->pluck('id')->all();

        // L'elenco deve contenere tutte e sole le immagini della versione, una volta ciascuna
        $data = $request->validate([
            'images' => ['required', 'array', 'size:'.count($ids)],
            'images.*' => ['integer', 'distinct', Rule::in($ids)],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['images'] as $position => $id) {
                Image::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return ImageResource::collection($version->images()->get());
    }

    /**
     * 	Elimina un’immagine e il suo file; la copertina passa alla prima rimasta.
     */
    public function destroy(Image $image): Response
    {
        Gate::authorize('update', $image->version);

        DB::transaction(function () use ($image) {
            $image->delete();

            // Se era la copertina, la passa alla prima immagine rimasta
            if ($image->is_cover) {
                Image::where('version_id', $image->version_id)
                    ->orderBy('sort_order')
                    ->first()
                    ?->update(['is_cover' => true]);
            }
        });

        return response()->noContent();
    }
}
