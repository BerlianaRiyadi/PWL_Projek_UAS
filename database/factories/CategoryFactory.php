<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_kategori' => $this->faker->word(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}