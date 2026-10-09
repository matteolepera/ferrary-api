<?php

namespace App\Models;

use App\Enums\PowertrainType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionPowertrain extends Model
{
    use HasFactory;

    protected $primaryKey = 'version_id';

    public $incrementing = false;

    protected $fillable = [
        'version_id',
        'engine_id',
        'ice_power_cv',
        'ice_torque_nm',
        'ice_max_rpm',
        'electric_motors_count',
        'electric_power_cv',
        'battery_kwh',
        'electric_range_km',
        'system_power_cv',
        'system_torque_nm',
        'gearbox_type',
        'gears',
        'drivetrain',
        'fuel_consumption_l100',
    ];

    protected function casts(): array
    {
        return [
            'powertrain_type' => PowertrainType::class,
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function engine(): BelongsTo
    {
        return $this->belongsTo(Engine::class);
    }
}
