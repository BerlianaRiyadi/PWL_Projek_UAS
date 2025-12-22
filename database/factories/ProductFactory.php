<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_produk' => $this->faker->words(2, true),
            'harga' => $this->faker->numberBetween(1000, 100000),
            'stok' => $this->faker->numberBetween(0, 100),
            'kategori_id' => \App\Models\Category::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}