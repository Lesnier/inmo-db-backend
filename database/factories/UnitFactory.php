<?php

namespace Database\Factories;

use App\DTOs\PropertyData;
use App\Models\Building;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $dto = new PropertyData();
        $dto->about = $this->faker->paragraph();
        $dto->address = $this->faker->address();
        $dto->amenities = $this->faker->randomElements(['Pool', 'Gym', 'Parking', 'WiFi'], 2);
        
        return [
            'building_id' => Building::factory(),
            'property_id' => null, // Can be linked later
            'floor' => $this->faker->numberBetween(1, 20),
            'unit_number' => $this->faker->bothify('##?'),
            'size_m2' => $this->faker->randomFloat(2, 50, 200),
            'bedrooms' => $this->faker->numberBetween(1, 4),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'price' => $this->faker->randomFloat(2, 50000, 500000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['available', 'sold', 'rented', 'reserved']),
            'data' => $dto->toArray(),
        ];
    }
}
