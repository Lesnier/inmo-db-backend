<?php

namespace App\DTOs\Property;

class Coordinates
{
    public float $lat;
    public float $lng;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->lat = (float)($data['lat'] ?? 0.0);
        $dto->lng = (float)($data['lng'] ?? 0.0);
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}
