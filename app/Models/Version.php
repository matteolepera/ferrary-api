<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Version extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

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
            'in_production' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

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

    public function images(): HasMany
    {
        return $this->hasMany(Image::class)->orderBy('sort_order');
    }

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
}
