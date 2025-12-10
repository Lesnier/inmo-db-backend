<?php

namespace App\DTOs\Property;

class ClimateRisk
{
    public ?int $fire = null;
    public ?int $flood = null;
    public ?int $storm = null;
    public ?int $heat = null;
    public array $additional_risks = [];

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->fire = $data['fire'] ?? null;
        $dto->flood = $data['flood'] ?? null;
        $dto->storm = $data['storm'] ?? null;
        $dto->heat = $data['heat'] ?? null;
        
        // Capture extra dynamic keys
        $knownKeys = ['fire', 'flood', 'storm', 'heat'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $knownKeys) && is_numeric($value)) {
                $dto->additional_risks[$key] = (float)$value;
            }
        }
        
        return $dto;
    }

    public function toArray(): array
    {
        $base = array_filter([
            'fire' => $this->fire,
            'flood' => $this->flood,
            'storm' => $this->storm,
            'heat' => $this->heat,
        ], fn($value) => !is_null($value));

        return array_merge($base, $this->additional_risks);
    }
}
