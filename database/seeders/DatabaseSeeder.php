<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default owner
        \App\Models\User::create([
            'name' => 'Owner',
            'email' => 'owner@kasir.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        // Create default kasir
        \App\Models\User::create([
            'name' => 'Kasir',
            'email' => 'kasir@kasir.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        // Create categories
        \App\Models\Category::create(['nama_kategori' => 'Makanan']);
        \App\Models\Category::create(['nama_kategori' => 'Minuman']);
        \App\Models\Category::create(['nama_kategori' => 'Snack']);

        // Create sample products
        \App\Models\Product::create([
            'nama_produk' => 'Nasi Goreng',
            'harga' => 15000,
            'stok' => 20,
            'kategori_id' => 1,
        ]);

        \App\Models\Product::create([
            'nama_produk' => 'Es Teh',
            'harga' => 5000,
            'stok' => 50,
            'kategori_id' => 2,
        ]);

        \App\Models\Product::create([
            'nama_produk' => 'Kerupuk',
            'harga' => 3000,
            'stok' => 2, // Low stock
            'kategori_id' => 3,
        ]);
    }
}