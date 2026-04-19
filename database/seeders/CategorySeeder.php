<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['slug' => 'sportifs',      'label' => 'Sportifs'],
            ['slug' => 'culturels',     'label' => 'Culturels'],
            ['slug' => 'scientifiques', 'label' => 'Scientifiques'],
            ['slug' => 'musicaux',      'label' => 'Musicaux'],
            ['slug' => 'commerciaux',   'label' => 'Commerciaux'],
        ];

        foreach ($defaults as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                ['label' => $cat['label'], 'is_default' => true]
            );
        }
    }
}
