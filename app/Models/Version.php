<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\ProductionType;
use App\Enums\VehicleType;
use App\Models\Concerns\LogsCatalogActivity;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property Carbon|null $published_at
 */
class Version extends Model
{
    use HasFactory, HasTranslations, LogsCatalogActivity, Publishable, SoftDeletes;

    public array $translatable = ['description'];

    protected $fillable = [
        'car_model_id',
        'name',
        'slug',
        'vehicle_type',
        'body_type',
        'project_code',
        'seats',
        'year_start',
        'year_end',
        'in_production',
        'production_type',
        'units_produced',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'body_type' => BodyType::class,
            'production_type' => ProductionType::class,
            'in_production' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CarModel, $this>
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function specs(): HasOne
    {
        return $this->hasOne(VersionSpec::class);
    }

    public function powertrain(): HasOne
    {
        return $this->hasOne(VersionPowertrain::class);
    }

    public function racingRecords(): HasMany
    {
        return $this->hasMany(VersionRacing::class);
    }

    /**
     * Le immagini della galleria, nell'ordine scelto.
     *
     * @return HasMany<Image, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class)->orderBy('sort_order');
    }

    /**
     * L'immagine di copertina della versione.
     *
     * @return HasOne<Image, $this>
     */
    public function cover(): HasOne
    {
        return $this->hasOne(Image::class)->where('is_cover', true);
    }

    public function designers(): BelongsToMany
    {
        return $this->belongsToMany(Designer::class)->withPivot('role');
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class);
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
