<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition()
    {
        $name = $this->faker->company() . ' Tower';
        $lat = $this->faker->latitude(-0.3, -0.1);
        $lng = $this->faker->longitude(-78.5, -78.4);

        return [
            'publisher_id' => User::factory(), 
            'publisher_type' => 'private_person',
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => $this->faker->address(),
            'country' => 'Ecuador',
            'state' => 'Pichincha',
            'city' => 'Quito',
            'district' => $this->faker->citySuffix(),
            'zip_code' => $this->faker->postcode(),
            'lat' => $lat,
            'lng' => $lng,
            // 'location' => DB::raw("ST_GeomFromText('POINT($lng $lat)')"), // Disabling for test stability
            'location' => null,
            'year_built' => $this->faker->year(),
            'floors' => $this->faker->numberBetween(5, 30),
            'data' => ['amenities' => ['Pool', 'Gym', 'Spa']],
        ];
    }

    
    public function configure()
    {
        return $this->afterMaking(function (Building $building) {
            if ($this->faker->boolean()) {
                $agent = Agent::factory()->create();
                $building->publisher_id = $agent->user_id; // FK to users
                $building->publisher_type = 'real_estate_agent';
            } else {
                 $user = User::factory()->create();
                 $building->publisher_id = $user->id;
                 $building->publisher_type = 'private_person';
            }
        });
    }
}
