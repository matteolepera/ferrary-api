<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property Carbon|null $published_at
 */
class CarModel extends Model
{
    use HasFactory, HasTranslations, LogsCatalogActivity, Publishable, SoftDeletes;

    public array $translatable = ['description'];

    protected $fillable = ['category_id', 'predecessor_id', 'name', 'slug', 'description'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'predecessor_id');
    }

    public function successors(): HasMany
    {
        return $this->hasMany(CarModel::class, 'predecessor_id');
    }

    /**
     * Le versioni del modello, nell'ordine scelto per il selettore.
     *
     * @return HasMany<Version, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Version::class)->orderBy('sort_order');
    }

    /**
     * La versione principale del modello: la prima pubblicata nell'ordine del selettore.
     * Serve soprattutto negli elenchi, per mostrare la copertina del modello.
     *
     * @return HasOne<Version, $this>
     */
    public function mainVersion(): HasOne
    {
        return $this->hasOne(Version::class)->ofMany(
            // Tra tutte le versioni prende quella con il sort_order più basso...
            ['sort_order' => 'min'],
            // ...considerando solo quelle pubblicate
            fn ($query) => $query->published(),
        );
    }
}
