<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Property;
use App\Models\User;

class RealEstateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías
        $apartment = Category::create([
            'name' => 'Apartment',
            'slug' => 'apartment',
            'data' => [],
        ]);
        $house = Category::create([
            'name' => 'House',
            'slug' => 'house',
            'data' => [],
        ]);
        $condo = Category::create([
            'name' => 'Condo',
            'slug' => 'condo',
            'data' => [],
        ]);
        $townhome = Category::create([
            'name' => 'Townhome',
            'slug' => 'townhome',
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

        // Crear un agente de ejemplo
        $agent = User::firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'John Smith',
                'password' => bcrypt('password'),
            ]
        );

        // Crear propiedades con los datos estáticos del frontend
        $properties = [
            [
                'title' => '40 S 9th St, Brooklyn, NY 11249',
                'price' => 1620,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '40 S 9th St, Brooklyn, NY 11249',
                    'general' => [
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'living_area' => '65 sq.m',
                    ],
                    'attributes' => [
                        'area' => 65,
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/01.jpg'],
                    'amenities' => ['Parking', 'WiFi'],
                    'about' => 'Beautiful apartment in Brooklyn',
                    'coordinates' => [
                        'lat' => 40.719,
                        'lng' => -73.994,
                    ],
                ],
                'badges' => ['Verified', 'New'],
                'verified' => true,
            ],
            [
                'title' => '517 82nd St, Brooklyn, NY 11209',
                'price' => 1320,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '517 82nd St, Brooklyn, NY 11209',
                    'general' => [
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'living_area' => '45 sq.m',
                    ],
                    'attributes' => [
                        'area' => 45,
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'parking' => 0,
                    ],
                    'images' => ['/img/listings/real-estate/02.jpg'],
                    'amenities' => ['WiFi'],
                    'about' => 'Cozy apartment in Brooklyn',
                    'coordinates' => [
                        'lat' => 40.7225,
                        'lng' => -73.998,
                    ],
                ],
                'badges' => ['New'],
                'verified' => false,
            ],
            [
                'title' => '3811 Ditmars Blvd Astoria, NY 11105',
                'price' => 1890,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '3811 Ditmars Blvd Astoria, NY 11105',
                    'general' => [
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'living_area' => '75 sq.m',
                    ],
                    'attributes' => [
                        'area' => 75,
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/03.jpg'],
                    'amenities' => ['Parking', 'WiFi', 'Air conditioning'],
                    'about' => 'Spacious apartment in Astoria',
                    'coordinates' => [
                        'lat' => 40.723,
                        'lng' => -73.990,
                    ],
                ],
                'badges' => ['Featured', 'New'],
                'verified' => false,
            ],
            [
                'title' => '67-04 Myrtle Ave Glendale, NY 11385',
                'price' => 1170,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '67-04 Myrtle Ave Glendale, NY 11385',
                    'general' => [
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'living_area' => '42 sq.m',
                    ],
                    'attributes' => [
                        'area' => 42,
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'parking' => 0,
                    ],
                    'images' => ['/img/listings/real-estate/04.jpg'],
                    'amenities' => [],
                    'about' => 'Affordable apartment in Glendale',
                    'coordinates' => [
                        'lat' => 40.718,
                        'lng' => -73.985,
                    ],
                ],
                'badges' => [],
                'verified' => false,
            ],
            [
                'title' => '444 Park Ave, Brooklyn, NY 11205',
                'price' => 1250,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '444 Park Ave, Brooklyn, NY 11205',
                    'general' => [
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'living_area' => '54 sq.m',
                    ],
                    'attributes' => [
                        'area' => 54,
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'parking' => 0,
                    ],
                    'images' => ['/img/listings/real-estate/05.jpg'],
                    'amenities' => ['WiFi'],
                    'about' => 'Modern apartment on Park Ave',
                    'coordinates' => [
                        'lat' => 40.7279,
                        'lng' => -74.0,
                    ],
                ],
                'badges' => ['Verified'],
                'verified' => true,
            ],
            [
                'title' => '929 Hart St, Brooklyn, NY 11237',
                'price' => 2750,
                'category_id' => $house->id,
                'data' => [
                    'address' => '929 Hart St, Brooklyn, NY 11237',
                    'general' => [
                        'bedrooms' => 3,
                        'bathrooms' => 2,
                        'living_area' => '108 sq.m',
                    ],
                    'attributes' => [
                        'area' => 108,
                        'bedrooms' => 3,
                        'bathrooms' => 2,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/06.jpg'],
                    'amenities' => ['Parking', 'WiFi', 'Air conditioning', 'Garage'],
                    'about' => 'Beautiful house with garage',
                    'coordinates' => [
                        'lat' => 40.7292,
                        'lng' => -73.996,
                    ],
                ],
                'badges' => ['New'],
                'verified' => false,
            ],
            [
                'title' => '123 Bedford Avenue, Brooklyn, NY 11211',
                'price' => 1490,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '123 Bedford Avenue, Brooklyn, NY 11211',
                    'general' => [
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'living_area' => '80 sq.m',
                    ],
                    'attributes' => [
                        'area' => 80,
                        'bedrooms' => 2,
                        'bathrooms' => 1,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/07.jpg'],
                    'amenities' => ['Parking', 'WiFi'],
                    'about' => 'Great location on Bedford Avenue',
                    'coordinates' => [
                        'lat' => 40.7264,
                        'lng' => -73.994,
                    ],
                ],
                'badges' => [],
                'verified' => false,
            ],
            [
                'title' => '124 Maple Street, Brooklyn, NY 11211',
                'price' => 1560,
                'category_id' => $apartment->id,
                'data' => [
                    'address' => '124 Maple Street, Brooklyn, NY 11211',
                    'general' => [
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'living_area' => '50 sq.m',
                    ],
                    'attributes' => [
                        'area' => 50,
                        'bedrooms' => 1,
                        'bathrooms' => 1,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/08.jpg'],
                    'amenities' => ['Parking', 'WiFi', 'Air conditioning'],
                    'about' => 'Nice apartment with parking',
                    'coordinates' => [
                        'lat' => 40.721,
                        'lng' => -74.004,
                    ],
                ],
                'badges' => ['Verified', 'New'],
                'verified' => true,
            ],
            [
                'title' => '212 Harrison Street, Brooklyn, NY 11240',
                'price' => 3860,
                'category_id' => $house->id,
                'data' => [
                    'address' => '212 Harrison Street, Brooklyn, NY 11240',
                    'general' => [
                        'bedrooms' => 3,
                        'bathrooms' => 2,
                        'living_area' => '130 sq.m',
                    ],
                    'attributes' => [
                        'area' => 130,
                        'bedrooms' => 3,
                        'bathrooms' => 2,
                        'parking' => 2,
                    ],
                    'images' => ['/img/listings/real-estate/09.jpg'],
                    'amenities' => ['Parking', 'WiFi', 'Air conditioning', 'Garage', 'Balcony'],
                    'about' => 'Luxury house with multiple parking spaces',
                    'coordinates' => [
                        'lat' => 40.7213,
                        'lng' => -74.001,
                    ],
                ],
                'badges' => [],
                'verified' => false,
            ],
            [
                'title' => '456 Court Street, Brooklyn, NY 11231',
                'price' => 2950,
                'category_id' => $house->id,
                'data' => [
                    'address' => '456 Court Street, Brooklyn, NY 11231',
                    'general' => [
                        'bedrooms' => 3,
                        'bathrooms' => 1,
                        'living_area' => '96 sq.m',
                    ],
                    'attributes' => [
                        'area' => 96,
                        'bedrooms' => 3,
                        'bathrooms' => 1,
                        'parking' => 1,
                    ],
                    'images' => ['/img/listings/real-estate/10.jpg'],
                    'amenities' => ['Parking', 'WiFi', 'Garage'],
                    'about' => 'Family house in great neighborhood',
                    'coordinates' => [
                        'lat' => 40.725,
                        'lng' => -74.002,
                    ],
                ],
                'badges' => [],
                'verified' => false,
            ],
        ];

        foreach ($properties as $propertyData) {
            $badges = $propertyData['badges'] ?? [];
            $verified = $propertyData['verified'] ?? false;
            unset($propertyData['badges'], $propertyData['verified']);

            Property::create([
                'agent_id' => $agent->id,
                'category_id' => $propertyData['category_id'],
                'operation_type' => 'rent',
                'type_of_offer' => 'real_estate_agent',
                'title' => $propertyData['title'],
                'slug' => \Illuminate\Support\Str::slug($propertyData['title']),
                'price' => $propertyData['price'],
                'currency' => 'USD',
                'status' => 'published',
                'published_at' => now(),
                'country' => 'USA',
                'state' => 'NY',
                'city' => 'New York',
                'district' => 'Brooklyn',
                'zip_code' => substr($propertyData['title'], -5),
                'street_address' => $propertyData['title'],
                'lat' => $propertyData['data']['coordinates']['lat'] ?? null,
                'lng' => $propertyData['data']['coordinates']['lng'] ?? null,
                'data' => array_merge($propertyData['data'], [
                    'badges' => $badges,
                    'verified' => $verified,
                ]),
            ]);
        }
    }
}
