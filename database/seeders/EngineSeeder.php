<?php

namespace Database\Seeders;

use App\Models\Engine;
use Illuminate\Database\Seeder;

class EngineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $engines = [
            [
                'code' => 'F120A',
                'name' => 'V8 biturbo F40',
                'architecture' => 'V',
                'v_angle_deg' => 90,
                'cylinders' => 8,
                'displacement_cc' => 2936,
                'bore_mm' => 82.00,
                'stroke_mm' => 69.50,
                'valves_per_cylinder' => 4,
                'aspiration' => 'turbo',
            ],
            [
                'code' => 'F140GA',
                'name' => 'V12 6.5',
                'architecture' => 'V',
                'v_angle_deg' => 65,
                'cylinders' => 12,
                'displacement_cc' => 6496,
                'bore_mm' => 94.00,
                'stroke_mm' => 78.00,
                'valves_per_cylinder' => 4,
                'aspiration' => 'aspirato',
            ],
            [
                'code' => 'F154FA',
                'name' => 'V8 4.0 biturbo',
                'architecture' => 'V',
                'v_angle_deg' => 90,
                'cylinders' => 8,
                'displacement_cc' => 3990,
                'bore_mm' => 88.00,
                'stroke_mm' => 82.00,
                'valves_per_cylinder' => 4,
                'aspiration' => 'turbo',
            ],
            [
                'code' => 'Tipo 053',
                'name' => 'V10 F1 3.0',
                'architecture' => 'V',
                'v_angle_deg' => 90,
                'cylinders' => 10,
                'displacement_cc' => 2997,
                'bore_mm' => null,
                'stroke_mm' => null,
                'valves_per_cylinder' => 4,
                'aspiration' => 'aspirato',
            ],
        ];

        foreach ($engines as $engine) {
            Engine::updateOrCreate(['code' => $engine['code']], $engine);
        }
    }
}
