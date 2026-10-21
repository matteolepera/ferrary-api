<?php

namespace App\Http\Resources\Admin;

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
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            'version_id' => $this->version_id,
            'url' => $disk->url($this->path),
            'alt_text' => $this->getTranslations('alt_text'),
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
        ];
    }
}
