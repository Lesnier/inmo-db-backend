<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'meeting', 'whatsapp', 'note']),
            'content' => $this->faker->paragraph(),
            'scheduled_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'status' => $this->faker->randomElement(['pending', 'completed']),
            'data' => [],
        ];
    }
}
