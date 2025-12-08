<?php

namespace App\DTOs;

class PropertyData
{
    // Basic fields
    public ?string $about = null;
    public ?string $address = null;

    // Arrays
    public array $amenities = [];
    public array $features = [];
    public array $images = [];
    public array $location_tags = [];
    public array $badges = [];

    // Objects/Nested structures
    public ?array $general = null;
    public ?array $attributes = null;
    public ?array $transportation = null;
    public ?array $coordinates = null;
    public ?array $financial = null;
    public ?array $valuation = null;
    public ?array $price_history = null;
    public ?array $climate_risk = null;
    public ?array $interior_features = null;
    public ?array $exterior_features = null;
    public ?array $neighborhood = null;

    // Boolean
    public bool $verified = false;

    public static function fromArray(array $arr): self
    {
        $dto = new self();

        // Basic fields
        $dto->about = $arr['about'] ?? null;
        $dto->address = $arr['address'] ?? null;

        // Arrays
        $dto->amenities = $arr['amenities'] ?? [];
        $dto->features = $arr['features'] ?? [];
        $dto->images = $arr['images'] ?? [];
        $dto->location_tags = $arr['location_tags'] ?? [];
        $dto->badges = $arr['badges'] ?? [];

        // Objects/Nested structures
        $dto->general = $arr['general'] ?? null;
        $dto->attributes = $arr['attributes'] ?? null;
        $dto->transportation = $arr['transportation'] ?? null;
        $dto->coordinates = $arr['coordinates'] ?? null;
        $dto->financial = $arr['financial'] ?? null;
        $dto->valuation = $arr['valuation'] ?? null;
        $dto->price_history = $arr['price_history'] ?? null;
        $dto->climate_risk = $arr['climate_risk'] ?? null;
        $dto->interior_features = $arr['interior_features'] ?? null;
        $dto->exterior_features = $arr['exterior_features'] ?? null;
        $dto->neighborhood = $arr['neighborhood'] ?? null;

        // Boolean
        $dto->verified = $arr['verified'] ?? false;

        return $dto;
    }

    public function toArray(): array
    {
        $data = [
            'about' => $this->about,
            'address' => $this->address,
            'amenities' => $this->amenities,
            'features' => $this->features,
            'images' => $this->images,
            'location_tags' => $this->location_tags,
            'badges' => $this->badges,
            'verified' => $this->verified,
        ];

        // Only include non-null nested objects
        if ($this->general !== null) {
            $data['general'] = $this->general;
        }
        if ($this->attributes !== null) {
            $data['attributes'] = $this->attributes;
        }
        if ($this->transportation !== null) {
            $data['transportation'] = $this->transportation;
        }
        if ($this->coordinates !== null) {
            $data['coordinates'] = $this->coordinates;
        }
        if ($this->financial !== null) {
            $data['financial'] = $this->financial;
        }
        if ($this->valuation !== null) {
            $data['valuation'] = $this->valuation;
        }
        if ($this->price_history !== null) {
            $data['price_history'] = $this->price_history;
        }
        if ($this->climate_risk !== null) {
            $data['climate_risk'] = $this->climate_risk;
        }
        if ($this->interior_features !== null) {
            $data['interior_features'] = $this->interior_features;
        }
        if ($this->exterior_features !== null) {
            $data['exterior_features'] = $this->exterior_features;
        }
        if ($this->neighborhood !== null) {
            $data['neighborhood'] = $this->neighborhood;
        }

        return $data;
    }
}
