<?php

namespace App\Models;

use App\Enums\BrakeType;
use App\Enums\ChassisMaterial;
use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionSpec extends Model
{
    use HasFactory, LogsCatalogActivity;

    protected $primaryKey = 'version_id';

    public $incrementing = false;

    protected $fillable = [
        'version_id',
        'length_mm',
        'width_mm',
        'height_mm',
        'wheelbase_mm',
        'dry_weight_kg',
        'weight_front_pct',
        'chassis_material',
        'brakes',
        'top_speed_kmh',
        'zero_to_100_sec',
        'zero_to_200_sec',
        'fiorano_lap_sec',
    ];

    protected function casts(): array
    {
        return [
            // I DECIMAL arrivano da MySQL come stringhe: li trasformiamo in numeri
            'zero_to_100_sec' => 'float',
            'zero_to_200_sec' => 'float',
            'fiorano_lap_sec' => 'float',
            'chassis_material' => ChassisMaterial::class,
            'brakes' => BrakeType::class,
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
