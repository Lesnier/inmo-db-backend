<?php

namespace App\DTOs;

class RequirementData
{
    public function __construct(
        public ?string $tipo = null,
        public ?string $categoria = null,
        public ?float $precio_min = null,
        public ?float $precio_max = null,
        public ?float $metros_min = null,
        public ?float $metros_max = null,
        public ?string $ubicacion = null,
        public ?int $bedrooms = null,
        public ?int $bathrooms = null,
        public ?array $features = [],
        public ?array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'precio_min' => $this->precio_min,
            'precio_max' => $this->precio_max,
            'metros_min' => $this->metros_min,
            'metros_max' => $this->metros_max,
            'ubicacion' => $this->ubicacion,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'features' => $this->features,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tipo: $data['tipo'] ?? null,
            categoria: $data['categoria'] ?? null,
            precio_min: isset($data['precio_min']) ? (float) $data['precio_min'] : null,
            precio_max: isset($data['precio_max']) ? (float) $data['precio_max'] : null,
            metros_min: isset($data['metros_min']) ? (float) $data['metros_min'] : null,
            metros_max: isset($data['metros_max']) ? (float) $data['metros_max'] : null,
            ubicacion: $data['ubicacion'] ?? null,
            bedrooms: isset($data['bedrooms']) ? (int) $data['bedrooms'] : null,
            bathrooms: isset($data['bathrooms']) ? (int) $data['bathrooms'] : null,
            features: $data['features'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }
}
