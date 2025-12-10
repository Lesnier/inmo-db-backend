<?php

namespace Database\Factories;

use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        return [
            'name' => 'Default Pipeline',
            'entity_type' => 'deal',
        ];
    }
}
