<?php

namespace App\DTOs\Property;

class Neighborhood
{
    public ?string $description = null;
    public ?int $walk_score = null;
    public ?int $bike_score = null;
    public ?int $transit_score = null;
    public array $highlights = [];
    public ?float $median_rent = null;
    public ?array $market_trends = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->description = $data['description'] ?? null;
        $dto->walk_score = $data['walk_score'] ?? null;
        $dto->bike_score = $data['bike_score'] ?? null;
        $dto->transit_score = $data['transit_score'] ?? null;
        $dto->highlights = $data['highlights'] ?? [];
        $dto->median_rent = isset($data['median_rent']) ? (float)$data['median_rent'] : null;
        $dto->market_trends = $data['market_trends'] ?? null;
        return $dto;
    }

    public function toArray(): array
    {
        return array_filter([
            'description' => $this->description,
            'walk_score' => $this->walk_score,
            'bike_score' => $this->bike_score,
            'transit_score' => $this->transit_score,
            'highlights' => $this->highlights,
            'median_rent' => $this->median_rent,
            'market_trends' => $this->market_trends,
        ], fn($value) => !is_null($value));
    }
}
