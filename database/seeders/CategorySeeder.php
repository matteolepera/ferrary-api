<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // La classificazione dell'archivio, ispirata a quella ufficiale Ferrari e di Ferrari Corse Clienti.
        // L'ordine è quello delle pillole dell'archivio e della sezione della home
        $categories = [
            ['sportive', 'Sportive', 'Sports cars'],
            ['gran-turismo', 'Gran Turismo', 'Grand tourers'],
            ['serie-speciali', 'Serie speciali', 'Special series'],
            ['supercar', 'Supercar', 'Supercars'],
            ['icona', 'Icona', 'Icona'],
            ['progetti-speciali', 'Progetti speciali', 'Special projects'],
            ['formula-1', 'Formula 1', 'Formula 1'],
            ['sport-prototipi', 'Sport prototipi', 'Sports prototypes'],
            ['gt-storiche', 'GT storiche', 'Historic GT racers'],
            ['gt2-gte', 'GT2 e GTE', 'GT2 and GTE'],
            ['gt3', 'GT3', 'GT3'],
            ['challenge', 'Challenge', 'Challenge'],
            ['programmi-xx', 'Programmi XX', 'XX Programmes'],
            ['concept', 'Concept', 'Concepts'],
        ];

        foreach ($categories as $position => [$slug, $it, $en]) {
            Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => ['it' => $it, 'en' => $en], 'sort_order' => $position + 1],
            );
        }
    }
}
