<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vincoli CHECK, raggruppati per tabella: nome del vincolo => condizione.
     *
     * @var array<string, array<string, string>>
     */
    private array $checks = [
        'categories' => [
            'chk_categories_name_it' => "JSON_EXTRACT(name, '$.it') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(name, '$.it')) <> ''",
        ],
        'car_models' => [
            'chk_car_models_description_it' => "JSON_EXTRACT(description, '$.it') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(description, '$.it')) <> ''",
        ],
        'images' => [
            'chk_images_alt_it' => "JSON_EXTRACT(alt_text, '$.it') IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(alt_text, '$.it')) <> ''",
        ],
        'versions' => [
            'chk_versions_years' => 'year_end IS NULL OR year_end >= year_start',
            'chk_versions_in_production' => 'in_production = 0 OR year_end IS NULL',
            'chk_versions_one_off' => "production_type <> 'one_off' OR units_produced IS NULL OR units_produced = 1",
            'chk_versions_f1_body' => "vehicle_type <> 'f1' OR body_type IS NULL OR body_type = 'monoposto'",
        ],
        'version_specs' => [
            'chk_specs_weight_pct' => 'weight_front_pct IS NULL OR weight_front_pct <= 100',
            'chk_specs_acceleration' => 'zero_to_100_sec IS NULL OR zero_to_200_sec IS NULL OR zero_to_200_sec > zero_to_100_sec',
        ],
        'version_powertrains' => [
            'chk_pt_has_propulsion' => 'engine_id IS NOT NULL OR electric_power_cv IS NOT NULL',
            'chk_pt_ice_data' => 'engine_id IS NOT NULL OR (ice_power_cv IS NULL AND ice_torque_nm IS NULL AND ice_max_rpm IS NULL AND fuel_consumption_l100 IS NULL)',
            'chk_pt_electric_data' => 'electric_power_cv IS NOT NULL OR (electric_motors_count IS NULL AND battery_kwh IS NULL AND electric_range_km IS NULL)',
        ],
        'engines' => [
            'chk_engines_v_angle' => "architecture = 'V' OR v_angle_deg IS NULL",
        ],
        'version_racing' => [
            'chk_racing_seasons' => 'season_end IS NULL OR season_end >= season_start',
            'chk_racing_wins_podiums' => 'wins IS NULL OR podiums IS NULL OR wins <= podiums',
            'chk_racing_podiums_races' => 'podiums IS NULL OR races IS NULL OR podiums <= races',
            'chk_racing_poles_races' => 'poles IS NULL OR races IS NULL OR poles <= races',
        ],
    ];

    public function up(): void
    {
        foreach ($this->checks as $table => $constraints) {
            foreach ($constraints as $name => $condition) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$condition})");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->checks as $table => $constraints) {
            foreach (array_keys($constraints) as $name) {
                DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
            }
        }
    }
};
