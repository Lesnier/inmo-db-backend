<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'Apartamento moderno en el centro',
            'Casa familiar con jardín',
            'Piso luminoso cerca del metro',
            'Chalet independiente con piscina',
            'Ático con terraza panorámica',
            'Estudio acogedor',
            'Duplex de lujo',
            'Piso reformado',
        ]);

        $propertyTypes = ['apartment', 'house', 'flat', 'penthouse', 'duplex'];
        $conditions = ['new_development', 'good_condition', 'to_renovate'];
        $floorTypes = ['ground_floor', 'middle_floor', 'top_floor'];
        $cities = ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga'];
        $city = $this->faker->randomElement($cities);

        $bedrooms = $this->faker->numberBetween(1, 5);
        $bathrooms = $this->faker->numberBetween(1, 3);
        $totalArea = $this->faker->numberBetween(50, 250);
        $livingArea = (int)($totalArea * 0.85);
        $price = $this->faker->numberBetween(150000, 1500000);

        return [
            'agent_id' => Agent::inRandomOrder()->first()?->id,
            'category_id' => Category::inRandomOrder()->first()?->id,
            'building_id' => $this->faker->boolean(30) ? Building::inRandomOrder()->first()?->id : null,
            'operation_type' => $this->faker->randomElement(['sell', 'rent']),
            'type_of_offer' => $this->faker->randomElement(['private_person', 'real_estate_agent']),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'price' => $price,
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['published', 'draft', 'archived']),
            'published_at' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
            'country' => 'España',
            'state' => $this->faker->randomElement(['Madrid', 'Cataluña', 'Valencia', 'Andalucía']),
            'city' => $city,
            'district' => $this->faker->citySuffix,
            'zip_code' => $this->faker->postcode,
            'street_address' => $this->faker->streetAddress,
            'lat' => $this->faker->latitude(36, 43),
            'lng' => $this->faker->longitude(-9, 3),
            'data' => [
                'about' => $this->faker->paragraph,
                'address' => $this->faker->address,
                'general' => [
                    'property_type' => $this->faker->randomElement($propertyTypes),
                    'condition' => $this->faker->randomElement($conditions),
                    'total_floors' => $this->faker->numberBetween(1, 10),
                    'floor_number' => $this->faker->numberBetween(1, 10),
                    'floor_type' => $this->faker->randomElement($floorTypes),
                    'total_area' => $totalArea,
                    'living_area' => $livingArea,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'parking_spots' => $this->faker->numberBetween(0, 2),
                    'occupancy_status' => $this->faker->randomElement(['available', 'rented']),
                    'year_built' => $this->faker->numberBetween(1950, 2024),
                ],
                'attributes' => [
                    'area' => $totalArea,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'parking' => $this->faker->numberBetween(0, 2),
                ],
                'amenities' => $this->faker->randomElements([
                    'Piscina', 'Gimnasio', 'Seguridad 24/7', 'Ascensor', 'Parqueadero',
                    'Terraza', 'Balcón', 'Aire acondicionado', 'Calefacción', 'Jardín'
                ], $this->faker->numberBetween(3, 7)),
                'features' => $this->faker->randomElements([
                    'Cocina equipada', 'Armarios empotrados', 'Suelo de parquet',
                    'Ventanas dobles', 'Orientación sur', 'Vistas despejadas'
                ], $this->faker->numberBetween(2, 5)),
                'images' => [],
                'location_tags' => $this->faker->randomElements([
                    'Centro', 'Playa', 'Montaña', 'Cerca metro', 'Zona residencial'
                ], $this->faker->numberBetween(1, 3)),
                'coordinates' => [
                    'lat' => $this->faker->latitude(36, 43),
                    'lng' => $this->faker->longitude(-9, 3),
                ],
                'badges' => $this->faker->randomElements([
                    'Nuevo', 'Reformado', 'Oportunidad', 'Premium'
                ], $this->faker->numberBetween(0, 2)),
                'verified' => $this->faker->boolean(60),
                'financial' => [
                    'hoa_fee' => $this->faker->numberBetween(50, 300),
                    'hoa_period' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
                    'price_per_sqm' => (int)($price / $totalArea),
                    'annual_tax' => $this->faker->numberBetween(500, 2000),
                    'listing_terms' => $this->faker->randomElements(['cash', 'conventional', 'mortgage'], 2),
                    'negotiable' => $this->faker->boolean(),
                    'agent_cooperation' => $this->faker->boolean(),
                ],
                'interior_features' => [
                    'heating' => $this->faker->randomElements(['central', 'electric', 'gas'], 1),
                    'cooling' => $this->faker->randomElements(['central_ac', 'split'], 1),
                    'flooring' => $this->faker->randomElements(['parquet', 'tile', 'marble'], 2),
                    'appliances' => $this->faker->randomElements(['refrigerator', 'oven', 'dishwasher'], 2),
                ],
                'exterior_features' => [
                    'porch' => $this->faker->boolean() ? 'covered' : null,
                    'courtyard' => $this->faker->boolean(),
                    'view' => $this->faker->randomElement(['city', 'mountain', 'sea', 'park']),
                ],
                'neighborhood' => [
                    'description' => $this->faker->paragraph,
                    'walk_score' => $this->faker->numberBetween(50, 100),
                    'bike_score' => $this->faker->numberBetween(40, 90),
                    'transit_score' => $this->faker->numberBetween(50, 100),
                    'highlights' => $this->faker->randomElements([
                        'Restaurantes cercanos', 'Parques', 'Escuelas', 'Transporte público'
                    ], 3),
                ],
            ],
        ];
    }
}
