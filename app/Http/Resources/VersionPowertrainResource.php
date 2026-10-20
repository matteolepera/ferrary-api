<?php

namespace App\Http\Resources;

use App\Models\VersionPowertrain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VersionPowertrain
 */
class VersionPowertrainResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Calcolato da MySQL: termico, ibrido o elettrico
            'type' => $this->powertrain_type,

            // whenLoaded: il motore compare solo se la relazione è stata caricata in anticipo
            // (con ->with('engine')). Se non lo è, la chiave viene omessa e non parte nessuna query extra.
            'engine' => new EngineResource($this->whenLoaded('engine')),

            // La sezione termica esiste solo se c'è un motore termico, altrimenti è null.
            // È la stessa regola che usa MySQL per calcolare il tipo di motorizzazione.
            'combustion' => $this->engine_id === null ? null : [
                'power_cv' => $this->ice_power_cv,
                'torque_nm' => $this->ice_torque_nm,
                'max_rpm' => $this->ice_max_rpm,
                'fuel_consumption_l100' => $this->fuel_consumption_l100,
            ],

            // Stesso discorso per la parte elettrica
            'electric' => $this->electric_power_cv === null ? null : [
                'motors_count' => $this->electric_motors_count,
                'power_cv' => $this->electric_power_cv,
                'battery_kwh' => $this->battery_kwh,
                'range_km' => $this->electric_range_km,
            ],

            // Potenza e coppia complessive del sistema (rilevanti soprattutto per le ibride)
            'system' => [
                'power_cv' => $this->system_power_cv,
                'torque_nm' => $this->system_torque_nm,
            ],

            'transmission' => [
                'gearbox_type' => $this->gearbox_type,
                'gears' => $this->gears,
                'drivetrain' => $this->drivetrain,
            ],
        ];
    }
}
