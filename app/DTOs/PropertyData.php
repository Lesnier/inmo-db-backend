<?php

namespace App\DTOs;

class PropertyData
{
    public ?string $about = null;
    public array $amenities = [];
    public array $images = [];
    public array $general = [];
    public array $transportation = [];
    public array $location_tags = [];
    public array $attributes = [];

    public static function fromArray(array $arr): self
    {
        $dto = new self();
        $dto->about = $arr['about'] ?? null;
        $dto->amenities = $arr['amenities'] ?? [];
        $dto->images = $arr['images'] ?? [];
        $dto->general = $arr['general'] ?? [];
        $dto->transportation = $arr['transportation'] ?? [];
        $dto->location_tags = $arr['location_tags'] ?? [];
        $dto->attributes = $arr['attributes'] ?? [];
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'about' => $this->about,
            'amenities' => $this->amenities,
            'images' => $this->images,
            'general' => $this->general,
            'transportation' => $this->transportation,
            'location_tags' => $this->location_tags,
            'attributes' => $this->attributes,
        ];
    }
}
