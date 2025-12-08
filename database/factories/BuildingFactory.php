<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Edificio ' . $this->faker->streetName,
            'Conjunto ' . $this->faker->lastName,
            'Condominio ' . $this->faker->colorName,
            'Torre ' . $this->faker->firstNameMale,
            'Residencial ' . $this->faker->city,
        ]);

        return [
            'agent_id' => Agent::inRandomOrder()->first()?->id,
            'user_id' => User::inRandomOrder()->first()?->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => $this->faker->streetAddress,
            'country' => $this->faker->randomElement(['España', 'México', 'Colombia', 'Argentina', 'Chile']),
            'state' => $this->faker->state,
            'city' => $this->faker->city,
            'district' => $this->faker->citySuffix,
            'zip_code' => $this->faker->postcode,
            'lat' => $this->faker->latitude,
            'lng' => $this->faker->longitude,
            'year_built' => $this->faker->numberBetween(1950, date('Y')),
            'floors' => $this->faker->numberBetween(2, 30),
            'data' => [
                'amenities' => $this->faker->randomElements([
                    'Piscina',
                    'Gimnasio',
                    'Seguridad 24/7',
                    'Ascensor',
                    'Parqueadero',
                    'Zonas verdes',
                    'Salón social',
                    'Juegos infantiles',
                    'Portería',
                ], $this->faker->numberBetween(3, 7)),
                'total_units' => $this->faker->numberBetween(10, 200),
                'available_units' => $this->faker->numberBetween(1, 20),
                'construction_company' => $this->faker->company,
                'management_company' => $this->faker->company,
                'monthly_maintenance_fee' => $this->faker->numberBetween(50, 500),
                'description' => $this->faker->paragraph,
            ],
        ];
    }
}
