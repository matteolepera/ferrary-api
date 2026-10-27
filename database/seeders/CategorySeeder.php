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
        // Le famiglie seguono la classificazione di Ferrari: per destinazione d'uso, non per motore.
        // L'ordine dell'array diventa il sort_order.
        $categories = [
            ['slug' => 'sportive', 'name' => ['it' => 'Sportive', 'en' => 'Sports cars']],
            ['slug' => 'gran-turismo', 'name' => ['it' => 'Gran Turismo', 'en' => 'Grand tourers']],
            ['slug' => 'hypercar', 'name' => ['it' => 'Hypercar', 'en' => 'Hypercars']],
            ['slug' => 'icona', 'name' => ['it' => 'Icona', 'en' => 'Icona']],
            ['slug' => 'corsa', 'name' => ['it' => 'Corsa', 'en' => 'Racing']],
            ['slug' => 'formula-1', 'name' => ['it' => 'Formula 1', 'en' => 'Formula 1']],
            ['slug' => 'concept', 'name' => ['it' => 'Concept', 'en' => 'Concepts']],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $index],
            );
        }
    }
}
