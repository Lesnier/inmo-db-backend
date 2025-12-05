<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Plan;

class RealEstateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías
        Category::create([
            'name' => 'Apartment',
            'slug' => 'apartment',
            'data' => [],
        ]);
        Category::create([
            'name' => 'House',
            'slug' => 'house',
            'data' => [],
        ]);
        Category::create([
            'name' => 'Office',
            'slug' => 'office',
            'data' => [],
        ]);

        // Crear planes
        Plan::create([
            'name' => 'Basic',
            'price' => 9.99,
            'period_days' => 30,
            'data' => ['features' => ['1 listing', 'Basic support']],
        ]);
        Plan::create([
            'name' => 'Pro',
            'price' => 29.99,
            'period_days' => 30,
            'data' => ['features' => ['10 listings', 'Priority support', 'Analytics']],
        ]);
    }
}
