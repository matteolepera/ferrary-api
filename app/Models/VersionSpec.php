<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionSpec extends Model
{
    use HasFactory;

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

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
