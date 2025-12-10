<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'meeting_type' => 'virtual',
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'duration_minutes' => 30,
            'created_by' => User::factory(),
            'host_id' => User::factory(),
            'location' => 'Zoom',
            'data' => [],
        ];
    }
}
