<?php

namespace Database\Factories;

use App\DTOs\Property\General;
use App\DTOs\Property\Coordinates;
use App\DTOs\PropertyData;
use App\Models\Agent;
use App\Models\Building;
use App\Models\Category;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        
        // Coordinates for Quito
        // lat: -0.25 to -0.05
        // lng: -78.58 to -78.45
        $lat = $this->faker->randomFloat(7, -0.25, -0.05);
        $lng = $this->faker->randomFloat(7, -78.58, -78.45);

        // Populate DTO logic (simulated array for simplicity/speed or use DTO if working)
        $data = [
            'about' => $this->faker->realText(),
            'address' => $this->faker->address(),
            'general' => [
                'bedrooms' => $this->faker->numberBetween(1, 5),
                'bathrooms' => $this->faker->numberBetween(1, 4),
                'parking_spots' => $this->faker->numberBetween(0, 3),
                'total_area' => $this->faker->numberBetween(50, 500),
                'year_built' => $this->faker->year(),
                'condition' => $this->faker->randomElement(['good_condition', 'new_development']),
                'property_type' => 'apartment',
            ],
            'amenities' => $this->faker->randomElements(['Pool', 'Gym', 'Spa', 'Elevator', 'Doorman'], 2),
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng
            ]
        ];

        return [
            // 'publisher_id' => \App\Models\User::factory(), // Moved to configure
            'publisher_type' => 'private_person',
            'category_id' => null, 
            'building_id' => null,
            'operation_type' => $this->faker->randomElement(['rent', 'sell']),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'price' => $this->faker->randomFloat(2, 50000, 1000000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'country' => 'Ecuador',
            'state' => 'Pichincha',
            'city' => 'Quito',
            'district' => $this->faker->citySuffix(),
            'zip_code' => $this->faker->postcode(),
            'street_address' => $this->faker->streetAddress(),
            'lat' => $lat,
            'lng' => $lng,
            'data' => PropertyData::fromArray($data),
        ] + (DB::getDriverName() === 'sqlite' ? [] : [
            'location' => DB::raw("ST_GeomFromText('POINT($lng $lat)')"),
        ]);
    }

    public function configure()
    {
        return $this->afterMaking(function (Property $property) {
            if ($property->publisher_id) {
                return;
            }

            if ($this->faker->boolean()) {
                $agent = Agent::factory()->create();
                $property->publisher_id = $agent->user_id; // FK to users
                $property->publisher_type = 'real_estate_agent';
            } else {
                 $user = \App\Models\User::factory()->create();
                 $property->publisher_id = $user->id;
                 $property->publisher_type = 'private_person';
            }
        });
    }
}
