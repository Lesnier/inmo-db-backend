<?php

namespace App\DTOs\Property;

class ExteriorFeatures
{
    public ?string $porch = null;
    public ?bool $courtyard = null;
    public ?bool $fruit_trees = null;
    public ?string $view = null;
    public array $additional_features = [];

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->porch = $data['porch'] ?? null;
        $dto->courtyard = $data['courtyard'] ?? null;
        $dto->fruit_trees = $data['fruit_trees'] ?? null;
        $dto->view = $data['view'] ?? null;

        $knownKeys = ['porch', 'courtyard', 'fruit_trees', 'view'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $knownKeys)) {
                $dto->additional_features[$key] = $value;
            }
        }

        return $dto;
    }

    public function toArray(): array
    {
        $base = array_filter([
            'porch' => $this->porch,
            'courtyard' => $this->courtyard,
            'fruit_trees' => $this->fruit_trees,
            'view' => $this->view,
        ], fn($value) => !is_null($value));

        return array_merge($base, $this->additional_features);
    }
}
