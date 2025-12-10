<?php

namespace App\DTOs;

use App\DTOs\Property\General;
use App\DTOs\Property\Financial;
use App\DTOs\Property\Valuation;
use App\DTOs\Property\PriceHistoryEntry;
use App\DTOs\Property\ClimateRisk;
use App\DTOs\Property\InteriorFeatures;
use App\DTOs\Property\ExteriorFeatures;
use App\DTOs\Property\Neighborhood;
use App\DTOs\Property\Coordinates;

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

    // Typed Nested Objects
    public ?General $general = null;
    public ?array $attributes = null; // Leaving as array for flexible extra attributes
    public ?array $transportation = null; // Leaving as array per request or if no specific type is needed yet
    public ?Coordinates $coordinates = null;
    public ?Financial $financial = null;
    public ?Valuation $valuation = null;
    
    /** @var PriceHistoryEntry[] */
    public array $price_history = [];
    
    public ?ClimateRisk $climate_risk = null;
    public ?InteriorFeatures $interior_features = null;
    public ?ExteriorFeatures $exterior_features = null;
    public ?Neighborhood $neighborhood = null;

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
        if (isset($arr['general'])) {
            $dto->general = General::fromArray($arr['general']);
        }
        
        $dto->attributes = $arr['attributes'] ?? null;
        $dto->transportation = $arr['transportation'] ?? null;

        if (isset($arr['coordinates'])) {
            $dto->coordinates = Coordinates::fromArray($arr['coordinates']);
        }
        if (isset($arr['financial'])) {
            $dto->financial = Financial::fromArray($arr['financial']);
        }
        if (isset($arr['valuation'])) {
            $dto->valuation = Valuation::fromArray($arr['valuation']);
        }

        if (isset($arr['price_history']) && is_array($arr['price_history'])) {
            $dto->price_history = array_map(
                fn($item) => PriceHistoryEntry::fromArray($item),
                $arr['price_history']
            );
        }

        if (isset($arr['climate_risk'])) {
            $dto->climate_risk = ClimateRisk::fromArray($arr['climate_risk']);
        }
        if (isset($arr['interior_features'])) {
            $dto->interior_features = InteriorFeatures::fromArray($arr['interior_features']);
        }
        if (isset($arr['exterior_features'])) {
            $dto->exterior_features = ExteriorFeatures::fromArray($arr['exterior_features']);
        }
        if (isset($arr['neighborhood'])) {
            $dto->neighborhood = Neighborhood::fromArray($arr['neighborhood']);
        }

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

        if ($this->general !== null) {
            $data['general'] = $this->general->toArray();
        }
        if ($this->attributes !== null) {
            $data['attributes'] = $this->attributes;
        }
        if ($this->transportation !== null) {
            $data['transportation'] = $this->transportation;
        }
        if ($this->coordinates !== null) {
            $data['coordinates'] = $this->coordinates->toArray();
        }
        if ($this->financial !== null) {
            $data['financial'] = $this->financial->toArray();
        }
        if ($this->valuation !== null) {
            $data['valuation'] = $this->valuation->toArray();
        }
        if (!empty($this->price_history)) {
            $data['price_history'] = array_map(
                fn($item) => $item->toArray(),
                $this->price_history
            );
        }
        if ($this->climate_risk !== null) {
            $data['climate_risk'] = $this->climate_risk->toArray();
        }
        if ($this->interior_features !== null) {
            $data['interior_features'] = $this->interior_features->toArray();
        }
        if ($this->exterior_features !== null) {
            $data['exterior_features'] = $this->exterior_features->toArray();
        }
        if ($this->neighborhood !== null) {
            $data['neighborhood'] = $this->neighborhood->toArray();
        }

        return $data;
    }
}
