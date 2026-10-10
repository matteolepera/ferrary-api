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
        $categories = [
            ['slug' => 'berlinetta-v8', 'name' => ['it' => 'Berlinetta V8', 'en' => 'V8 Berlinetta']],
            ['slug' => 'berlinetta-v12', 'name' => ['it' => 'Berlinetta V12', 'en' => 'V12 Berlinetta']],
            ['slug' => 'hypercar', 'name' => ['it' => 'Hypercar', 'en' => 'Hypercar']],
            ['slug' => 'formula-1', 'name' => ['it' => 'Formula 1', 'en' => 'Formula 1']],
            ['slug' => 'sport-prototipi', 'name' => ['it' => 'Sport prototipi', 'en' => 'Sports prototypes']],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $index],
            );
        }
    }
}
