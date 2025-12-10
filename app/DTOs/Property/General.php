<?php

namespace App\DTOs\Property;

class General
{
    public ?string $property_type = null;
    public ?string $property_subtype = null;
    public ?string $condition = null;
    public ?int $total_floors = null;
    public ?int $floor_number = null;
    public ?string $interior_number = null;
    public ?string $exterior_number = null;
    public ?string $floor_type = null;
    public ?float $total_area = null;
    public ?float $living_area = null;
    public ?float $kitchen_area = null;
    public ?int $bedrooms = null;
    public ?int $bathrooms = null;
    public ?int $parking_spots = null;
    public ?string $occupancy_status = null;
    public ?int $year_built = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->property_type = $data['property_type'] ?? null;
        $dto->property_subtype = $data['property_subtype'] ?? null;
        $dto->condition = $data['condition'] ?? null;
        $dto->total_floors = $data['total_floors'] ?? null;
        $dto->floor_number = $data['floor_number'] ?? null;
        $dto->interior_number = $data['interior_number'] ?? null;
        $dto->exterior_number = $data['exterior_number'] ?? null;
        $dto->floor_type = $data['floor_type'] ?? null;
        $dto->total_area = isset($data['total_area']) ? (float)$data['total_area'] : null;
        $dto->living_area = isset($data['living_area']) ? (float)$data['living_area'] : null;
        $dto->kitchen_area = isset($data['kitchen_area']) ? (float)$data['kitchen_area'] : null;
        $dto->bedrooms = $data['bedrooms'] ?? null;
        $dto->bathrooms = $data['bathrooms'] ?? null;
        $dto->parking_spots = $data['parking_spots'] ?? null;
        $dto->occupancy_status = $data['occupancy_status'] ?? null;
        $dto->year_built = $data['year_built'] ?? null;

        return $dto;
    }

    public function toArray(): array
    {
        return array_filter([
            'property_type' => $this->property_type,
            'property_subtype' => $this->property_subtype,
            'condition' => $this->condition,
            'total_floors' => $this->total_floors,
            'floor_number' => $this->floor_number,
            'interior_number' => $this->interior_number,
            'exterior_number' => $this->exterior_number,
            'floor_type' => $this->floor_type,
            'total_area' => $this->total_area,
            'living_area' => $this->living_area,
            'kitchen_area' => $this->kitchen_area,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'parking_spots' => $this->parking_spots,
            'occupancy_status' => $this->occupancy_status,
            'year_built' => $this->year_built,
        ], fn($value) => !is_null($value));
    }
}
