<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        // Avatar logic
        $avatar = 'https://i.pravatar.cc/300?img=' . $this->faker->numberBetween(1, 70);

        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(['active', 'pending', 'suspended']),
            'onboarding_status' => 'completed',
            'plan_id' => null,
            'data' => \App\DTOs\AgentData::fromArray([
                'company_name' => $this->faker->company(),
                'license_number' => $this->faker->bothify('LIC-#####'),
                'phone' => $this->faker->phoneNumber(),
                'avatar' => $avatar,
                'bio' => $this->faker->paragraph(),
                'specialties' => ['Residential', 'Luxury', 'Commercial'],
                'languages' => ['Spanish', 'English'],
            ]),
        ];
    }
}
