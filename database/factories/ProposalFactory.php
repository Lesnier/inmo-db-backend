<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'title' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['enviada', 'vista', 'aceptada', 'rechazada']),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'data' => [],
        ];
    }
}
