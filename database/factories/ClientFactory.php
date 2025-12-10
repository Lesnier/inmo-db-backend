<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'publisher_id' => function() {
                return Agent::factory()->create()->user_id;
            },
            'publisher_type' => 'real_estate_agent',
            'contact_id' => Contact::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'data' => [],
        ];
    }
}
