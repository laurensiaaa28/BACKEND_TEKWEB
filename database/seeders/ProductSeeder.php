<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $isiUlang = Category::where('name', 'Galon Isi Ulang')->value('id');
        $galonBaru = Category::where('name', 'Galon Baru + Isi')->value('id');
        $aksesoris = Category::where('name', 'Aksesoris Galon')->value('id');

        $products = [
            [
                'category_id' => $isiUlang,
                'name' => 'Isi Ulang Air Mineral 19L',
                'description' => 'Isi ulang air mineral galon 19 liter, diantar ke rumah.',
                'price' => 7000,
                'stock' => 200,
            ],
            [
                'category_id' => $isiUlang,
                'name' => 'Isi Ulang Air Alkali 19L',
                'description' => 'Isi ulang air alkali pH tinggi galon 19 liter.',
                'price' => 12000,
                'stock' => 100,
            ],
            [
                'category_id' => $isiUlang,
                'name' => 'Isi Ulang Air Demineral 19L',
                'description' => 'Isi ulang air demineral galon 19 liter.',
                'price' => 10000,
                'stock' => 100,
            ],
            [
                'category_id' => $galonBaru,
                'name' => 'Galon Baru 19L + Isi Air Mineral',
                'description' => 'Paket galon baru 19 liter beserta isi air mineral.',
                'price' => 55000,
                'stock' => 30,
            ],
            [
                'category_id' => $galonBaru,
                'name' => 'Galon Baru 12L + Isi Air Mineral',
                'description' => 'Paket galon baru 12 liter beserta isi air mineral.',
                'price' => 40000,
                'stock' => 20,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Pompa Galon Elektrik',
                'description' => 'Pompa galon elektrik isi ulang USB.',
                'price' => 45000,
                'stock' => 25,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Tutup Galon (isi 5 pcs)',
                'description' => 'Tutup galon cadangan, isi 5 pcs.',
                'price' => 10000,
                'stock' => 100,
            ],
            [
                'category_id' => $aksesoris,
                'name' => 'Dispenser Meja',
                'description' => 'Dispenser galon atas model meja, air panas dan normal.',
                'price' => 250000,
                'stock' => 8,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product + [
                'slug' => Str::slug($product['name']),
                'is_active' => true,
            ]);
        }
    }
}