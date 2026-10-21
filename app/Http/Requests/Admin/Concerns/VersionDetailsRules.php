<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\BodyType;
use App\Enums\BrakeType;
use App\Enums\ChassisMaterial;
use App\Enums\Drivetrain;
use App\Enums\GearboxType;
use App\Enums\ProductionType;
use App\Enums\VehicleType;
use Illuminate\Validation\Rule;

/**
 * Regole delle sezioni "specs" e "powertrain", condivise tra creazione e modifica.
 * Ripetono i vincoli CHECK del database per restituire errori leggibili.
 */
trait VersionDetailsRules
{
    /**
     * @return array<string, mixed>
     */
    protected function versionRules(): array
    {
        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'body_type' => [
                'nullable',
                Rule::enum(BodyType::class),
                Rule::when($this->input('vehicle_type') === VehicleType::F1->value, [Rule::in([BodyType::Monoposto->value])]),
            ],
            'project_code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'seats' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9'],
            'year_start' => ['required', 'integer', 'min:1947', 'max:2100'],
            'year_end' => ['nullable', 'integer', 'gte:year_start', 'max:2100', 'prohibited_if:in_production,true'],
            'in_production' => ['sometimes', 'boolean'],
            'production_type' => ['nullable', Rule::enum(ProductionType::class)],
            'units_produced' => [
                'nullable',
                'integer',
                'min:1',
                Rule::when($this->input('production_type') === ProductionType::OneOff->value, ['max:1']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function specsRules(): array
    {
        return [
            'specs' => ['sometimes', 'nullable', 'array'],
            'specs.length_mm' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'specs.width_mm' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'specs.height_mm' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'specs.wheelbase_mm' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'specs.dry_weight_kg' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'specs.weight_front_pct' => ['nullable', 'integer', 'min:0', 'max:100'],
            'specs.chassis_material' => ['nullable', Rule::enum(ChassisMaterial::class)],
            'specs.brakes' => ['nullable', Rule::enum(BrakeType::class)],
            'specs.top_speed_kmh' => ['nullable', 'integer', 'min:1', 'max:600'],
            'specs.zero_to_100_sec' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'specs.zero_to_200_sec' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.9',
                Rule::when(filled($this->input('specs.zero_to_100_sec')), ['gt:specs.zero_to_100_sec']),
            ],
            'specs.fiorano_lap_sec' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function powertrainRules(): array
    {
        $hasPowertrain = is_array($this->input('powertrain'));

        return [
            'powertrain' => ['sometimes', 'nullable', 'array'],

            // Serve almeno un motore termico o una parte elettrica
            'powertrain.engine_id' => [
                'nullable',
                'integer',
                'exists:engines,id',
                Rule::requiredIf($hasPowertrain && blank($this->input('powertrain.electric_power_cv'))),
            ],

            // Dati termici: solo se c'è un motore termico
            'powertrain.ice_power_cv' => ['nullable', 'integer', 'min:1', 'max:65535', 'prohibited_if:powertrain.engine_id,null'],
            'powertrain.ice_torque_nm' => ['nullable', 'integer', 'min:1', 'max:65535', 'prohibited_if:powertrain.engine_id,null'],
            'powertrain.ice_max_rpm' => ['nullable', 'integer', 'min:1', 'max:65535', 'prohibited_if:powertrain.engine_id,null'],
            'powertrain.fuel_consumption_l100' => ['nullable', 'numeric', 'min:0', 'max:999.9', 'prohibited_if:powertrain.engine_id,null'],

            // Dati elettrici: solo se c'è la potenza elettrica
            'powertrain.electric_power_cv' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'powertrain.electric_motors_count' => ['nullable', 'integer', 'min:1', 'max:10', 'prohibited_if:powertrain.electric_power_cv,null'],
            'powertrain.battery_kwh' => ['nullable', 'numeric', 'min:0', 'max:999.99', 'prohibited_if:powertrain.electric_power_cv,null'],
            'powertrain.electric_range_km' => ['nullable', 'integer', 'min:0', 'max:65535', 'prohibited_if:powertrain.electric_power_cv,null'],

            'powertrain.system_power_cv' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'powertrain.system_torque_nm' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'powertrain.gearbox_type' => ['nullable', Rule::enum(GearboxType::class)],
            'powertrain.gears' => ['nullable', 'integer', 'min:1', 'max:10'],
            'powertrain.drivetrain' => ['nullable', Rule::enum(Drivetrain::class)],
        ];
    }
}
