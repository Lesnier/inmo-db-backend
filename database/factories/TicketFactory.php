<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        // Ensure at least one pipeline exists or creating one.
        $pipeline = Pipeline::where('entity_type', 'ticket')->first() ?? Pipeline::factory()->create(['entity_type' => 'ticket']);
        $stage = $pipeline->stages()->inRandomOrder()->first() ?? PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        return [
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['support', 'requirement', 'incident']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => 'open',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => User::factory(),
            'data' => [],
        ];
    }
}
