<?php

namespace Database\Factories;

use App\Models\PipelineStage;
use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineStageFactory extends Factory
{
    protected $model = PipelineStage::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'name' => 'New Stage',
            'position' => 0,
            'probability' => 10,
        ];
    }
}
