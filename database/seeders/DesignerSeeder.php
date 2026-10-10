<?php

namespace Database\Seeders;

use App\Models\Designer;
use Illuminate\Database\Seeder;

class DesignerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designers = [
            ['slug' => 'pininfarina', 'name' => 'Pininfarina', 'type' => 'studio', 'country' => 'IT'],
            ['slug' => 'centro-stile-ferrari', 'name' => 'Centro Stile Ferrari', 'type' => 'studio', 'country' => 'IT'],
            ['slug' => 'leonardo-fioravanti', 'name' => 'Leonardo Fioravanti', 'type' => 'persona', 'country' => 'IT'],
            ['slug' => 'flavio-manzoni', 'name' => 'Flavio Manzoni', 'type' => 'persona', 'country' => 'IT'],
        ];

        foreach ($designers as $designer) {
            Designer::updateOrCreate(['slug' => $designer['slug']], $designer);
        }
    }
}
