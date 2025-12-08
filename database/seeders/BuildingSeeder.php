<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Agent;
use Illuminate\Support\Str;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first agent for assignment
        $agent = Agent::first();

        $buildings = [
            [
                'name' => 'Torre Diamante',
                'address' => 'Calle Principal 123',
                'city' => 'Madrid',
                'state' => 'Madrid',
                'country' => 'España',
                'zip_code' => '28001',
                'lat' => 40.4168,
                'lng' => -3.7038,
                'year_built' => 2018,
                'floors' => 15,
                'data' => [
                    'amenities' => ['Piscina', 'Gimnasio', 'Seguridad 24/7', 'Ascensor', 'Parqueadero'],
                    'total_units' => 60,
                    'available_units' => 5,
                    'construction_company' => 'Constructora Elite S.A.',
                    'monthly_maintenance_fee' => 150,
                    'description' => 'Moderno edificio residencial en el corazón de Madrid',
                ],
            ],
            [
                'name' => 'Conjunto Residencial Los Robles',
                'address' => 'Avenida Libertador 456',
                'city' => 'Barcelona',
                'state' => 'Cataluña',
                'country' => 'España',
                'zip_code' => '08002',
                'lat' => 41.3851,
                'lng' => 2.1734,
                'year_built' => 2015,
                'floors' => 8,
                'data' => [
                    'amenities' => ['Zonas verdes', 'Juegos infantiles', 'Salón social', 'Portería', 'Parqueadero'],
                    'total_units' => 80,
                    'available_units' => 8,
                    'construction_company' => 'Desarrollos Urbanos BCN',
                    'monthly_maintenance_fee' => 120,
                    'description' => 'Amplio conjunto residencial con áreas verdes',
                ],
            ],
            [
                'name' => 'Condominio Vista Mar',
                'address' => 'Paseo Marítimo 789',
                'city' => 'Valencia',
                'state' => 'Valencia',
                'country' => 'España',
                'zip_code' => '46003',
                'lat' => 39.4699,
                'lng' => -0.3763,
                'year_built' => 2020,
                'floors' => 12,
                'data' => [
                    'amenities' => ['Piscina', 'Gimnasio', 'Terraza panorámica', 'Ascensor', 'Seguridad 24/7'],
                    'total_units' => 48,
                    'available_units' => 3,
                    'construction_company' => 'Costa Inmobiliaria',
                    'monthly_maintenance_fee' => 180,
                    'description' => 'Exclusivo condominio frente al mar con vistas espectaculares',
                ],
            ],
            [
                'name' => 'Edificio Santa Ana',
                'address' => 'Calle Mayor 234',
                'city' => 'Sevilla',
                'state' => 'Andalucía',
                'country' => 'España',
                'zip_code' => '41001',
                'lat' => 37.3886,
                'lng' => -5.9823,
                'year_built' => 2010,
                'floors' => 6,
                'data' => [
                    'amenities' => ['Ascensor', 'Portería', 'Parqueadero'],
                    'total_units' => 24,
                    'available_units' => 2,
                    'construction_company' => 'Andalucía Construcciones',
                    'monthly_maintenance_fee' => 80,
                    'description' => 'Edificio clásico en zona histórica de Sevilla',
                ],
            ],
            [
                'name' => 'Torre Central Park',
                'address' => 'Avenida Diagonal 567',
                'city' => 'Barcelona',
                'state' => 'Cataluña',
                'country' => 'España',
                'zip_code' => '08029',
                'lat' => 41.3947,
                'lng' => 2.1478,
                'year_built' => 2022,
                'floors' => 20,
                'data' => [
                    'amenities' => ['Piscina infinity', 'Gimnasio premium', 'Spa', 'Coworking', 'Seguridad 24/7', 'Concierge'],
                    'total_units' => 100,
                    'available_units' => 12,
                    'construction_company' => 'Luxury Developments Group',
                    'monthly_maintenance_fee' => 250,
                    'description' => 'Torre premium con servicios de lujo y tecnología de vanguardia',
                ],
            ],
        ];

        foreach ($buildings as $buildingData) {
            $buildingData['agent_id'] = $agent?->id;
            $buildingData['slug'] = Str::slug($buildingData['name']);

            Building::create($buildingData);
        }

        // Create additional random buildings using factory
        Building::factory(10)->create();
    }
}
