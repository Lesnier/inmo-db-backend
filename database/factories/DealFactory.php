<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        // Ensure at least one pipeline exists or creating one.
        // Ideally seeders run first, but for factory safety:
        $pipeline = Pipeline::first() ?? Pipeline::factory()->create(['entity_type' => 'deal']);
        $stage = $pipeline->stages()->inRandomOrder()->first() ?? PipelineStage::factory()->create(['pipeline_id' => $pipeline->id]);

        return [
            'title' => $this->faker->sentence(3),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['open', 'won', 'lost', 'archived']),
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => User::factory(),
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+3 months'),
            'data' => [],
        ];
    }
}
