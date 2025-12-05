<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use App\DTOs\PropertyData;

class PropertyDataCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        $arr = $value ? json_decode($value, true) : [];
        return PropertyData::fromArray($arr);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof PropertyData) {
            return json_encode($value->toArray());
        }
        return json_encode($value ?? []);
    }
}
