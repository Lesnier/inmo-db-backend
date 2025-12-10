<?php

namespace Database\Factories;

use App\Models\DealStageHistory;
use App\Models\Deal;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealStageHistoryFactory extends Factory
{
    protected $model = DealStageHistory::class;

    public function definition()
    {
        return [
            'deal_id' => Deal::factory(),
            'stage_id' => PipelineStage::factory(),
            'pipeline_id' => function (array $attributes) {
                return PipelineStage::find($attributes['stage_id'])->pipeline_id;
            },
            'entered_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'exited_at' => null, // Often null for current stage
            'duration_minutes' => null,
        ];
    }
}
