<?php

namespace App\DTOs\Property;

class InteriorFeatures
{
    public array $heating = [];
    public array $cooling = [];
    public array $flooring = [];
    public array $windows = [];
    public array $appliances = [];
    public ?string $laundry = null;
    public array $extra_rooms = [];

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->heating = $data['heating'] ?? [];
        $dto->cooling = $data['cooling'] ?? [];
        $dto->flooring = $data['flooring'] ?? [];
        $dto->windows = $data['windows'] ?? [];
        $dto->appliances = $data['appliances'] ?? [];
        $dto->laundry = $data['laundry'] ?? null;
        $dto->extra_rooms = $data['extra_rooms'] ?? [];
        return $dto;
    }

    public function toArray(): array
    {
        return array_filter([
            'heating' => $this->heating,
            'cooling' => $this->cooling,
            'flooring' => $this->flooring,
            'windows' => $this->windows,
            'appliances' => $this->appliances,
            'laundry' => $this->laundry,
            'extra_rooms' => $this->extra_rooms,
        ], fn($value) => !is_null($value) && $value !== []);
    }
}
