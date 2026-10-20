<?php

namespace App\Http\Resources;

use App\Models\Image;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Image
 */
class ImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Storage::disk() dichiara un'interfaccia generica, senza il metodo url().
        // Il disco 'public' è in realtà un FilesystemAdapter, che url() ce l'ha:
        // il commento @var lo dice a Larastan e all'editor.
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            // Nel database salviamo solo il percorso relativo; al frontend serve l'indirizzo completo
            'url' => $disk->url($this->path),
            'alt_text' => $this->alt_text,
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
        ];
    }
}
