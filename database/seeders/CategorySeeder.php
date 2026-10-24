<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Galon Isi Ulang',
            'Galon Baru + Isi',
            'Aksesoris Galon',
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}