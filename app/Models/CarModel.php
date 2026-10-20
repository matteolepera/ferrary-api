<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
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

    /**
     * @return BelongsTo<CarModel, $this>
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'predecessor_id');
    }

    /**
     * @return HasMany<CarModel, $this>
     */
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

    /**
     * Visibile al pubblico: pubblicato e con almeno una versione pubblicata.
     *
     * @param  Builder<CarModel>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->published()
            ->whereHas('versions', fn ($query) => $query->published());
    }

    /**
     * Filtri dell'archivio pubblico. Le chiavi assenti vengono ignorate.
     *
     * @param  Builder<CarModel>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query
                ->whereRelation('category', 'slug', $slug))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                // Raggruppa l'OR senza toccare le altre condizioni
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('versions', fn ($query) => $query
                        ->published()
                        ->where('name', 'like', "%{$search}%"))))
            // Tutti i filtri sulle versioni devono valere per la stessa versione
            ->when(Arr::hasAny($filters, ['vehicle_type', 'powertrain', 'year_from', 'year_to']), fn ($query) => $query
                ->whereHas('versions', fn ($query) => $query
                    ->published()
                    ->when($filters['vehicle_type'] ?? null, fn ($query, $type) => $query
                        ->where('vehicle_type', $type))
                    ->when($filters['powertrain'] ?? null, fn ($query, $type) => $query
                        ->whereRelation('powertrain', 'powertrain_type', $type))
                    // Prodotta almeno fino a quell'anno (o ancora in produzione)
                    ->when($filters['year_from'] ?? null, fn ($query, $year) => $query
                        ->where(fn ($query) => $query
                            ->whereNull('year_end')
                            ->orWhere('year_end', '>=', $year)))
                    // Prodotta a partire da quell'anno o prima
                    ->when($filters['year_to'] ?? null, fn ($query, $year) => $query
                        ->where('year_start', '<=', $year))));
    }

    /**
     * Ordinamento dell'archivio: name, -name, year, -year.
     *
     * @param  Builder<CarModel>  $query
     */
    public function scopeSorted(Builder $query, ?string $sort): void
    {
        $direction = str_starts_with((string) $sort, '-') ? 'desc' : 'asc';

        match (ltrim((string) $sort, '-')) {
            // Anno della prima versione pubblicata, poi nome a parità di anno
            'year' => $query
                ->orderBy(
                    Version::query()
                        ->selectRaw('min(year_start)')
                        ->whereColumn('versions.car_model_id', 'car_models.id')
                        ->published(),
                    $direction,
                )
                ->orderBy('name'),
            default => $query->orderBy('name', $direction),
        };
    }
}
