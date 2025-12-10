<?php

namespace App\DTOs\Property;

class Valuation
{
    public ?float $market_value = null;
    public ?float $low_range = null;
    public ?float $high_range = null;
    public ?float $confidence = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->market_value = isset($data['market_value']) ? (float)$data['market_value'] : null;
        $dto->low_range = isset($data['low_range']) ? (float)$data['low_range'] : null;
        $dto->high_range = isset($data['high_range']) ? (float)$data['high_range'] : null;
        $dto->confidence = isset($data['confidence']) ? (float)$data['confidence'] : null;
        return $dto;
    }

    public function toArray(): array
    {
        return array_filter([
            'market_value' => $this->market_value,
            'low_range' => $this->low_range,
            'high_range' => $this->high_range,
            'confidence' => $this->confidence,
        ], fn($value) => !is_null($value));
    }
}
