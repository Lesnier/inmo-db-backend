<?php

namespace App\Casts;

use App\DTOs\AgentData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AgentDataCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): AgentData
    {
        $data = json_decode($value, true) ?? [];
        return AgentData::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof AgentData) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return json_encode([]);
    }
}
