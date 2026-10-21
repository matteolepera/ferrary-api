<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Translatable\HasTranslations;

class Engine extends Model
{
    use HasFactory, HasTranslations, LogsCatalogActivity;

    public array $translatable = ['description'];

    protected $fillable = [
        'code',
        'name',
        'architecture',
        'v_angle_deg',
        'cylinders',
        'displacement_cc',
        'bore_mm',
        'stroke_mm',
        'valves_per_cylinder',
        'aspiration',
        'description',
    ];

    protected function casts(): array
    {
        return [
            // DECIMAL arriva da MySQL come stringa ("82.00"): il cast lo trasforma in numero (82.0)
            'bore_mm' => 'float',
            'stroke_mm' => 'float',
        ];
    }

    /**
     * Le versioni che montano questo motore, attraverso le motorizzazioni.
     *
     * @return HasManyThrough<Version, VersionPowertrain, $this>
     */
    public function versions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Version::class,           // la tabella che vogliamo raggiungere
            VersionPowertrain::class, // la tabella intermedia
            'engine_id',              // colonna della tabella intermedia che punta al motore
            'id',                     // colonna di versions usata per il collegamento
            'id',                     // colonna di engines
            'version_id',             // colonna della tabella intermedia che punta alla versione
        );
    }
}
