<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Galonin',
            'email' => 'admin@galonin.test',
            'password' => 'password123',
            'phone' => '081234567890',
            'address' => 'Kantor Pusat Galonin',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Budi Pelanggan',
            'email' => 'budi@galonin.test',
            'password' => 'password123',
            'phone' => '081298765432',
            'address' => 'Jl. Melati No. 10',
            'role' => 'customer',
        ]);
    }
}