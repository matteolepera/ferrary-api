<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Image extends Model
{
    use HasFactory, HasTranslations, LogsCatalogActivity;

    public array $translatable = ['alt_text'];

    protected $fillable = ['version_id', 'path', 'alt_text', 'is_cover', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    /**
     * Quando un'immagine viene eliminata, elimina anche il suo file,
     * ma solo dopo che il database ha confermato l'eliminazione.
     */
    protected static function booted(): void
    {
        static::deleted(function (Image $image) {
            DB::afterCommit(fn () => Storage::disk('public')->delete($image->path));
        });
    }
}
