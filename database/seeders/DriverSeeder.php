<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $drivers = [
            [
                'slug' => 'michael-schumacher',
                'first_name' => 'Michael',
                'last_name' => 'Schumacher',
                'nationality' => 'DE',
                'birth_date' => '1969-01-03',
            ],
            [
                'slug' => 'rubens-barrichello',
                'first_name' => 'Rubens',
                'last_name' => 'Barrichello',
                'nationality' => 'BR',
                'birth_date' => '1972-05-23',
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::updateOrCreate(['slug' => $driver['slug']], $driver);
        }
    }
}
