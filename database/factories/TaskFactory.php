<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'data' => [],
        ];
    }
}
