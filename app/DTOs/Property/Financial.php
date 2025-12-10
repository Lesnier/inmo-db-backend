<?php

namespace App\DTOs\Property;

class Financial
{
    public ?float $hoa_fee = null;
    public ?string $hoa_period = null;
    public ?float $price_per_sqm = null;
    public ?float $annual_tax = null;
    public array $listing_terms = [];
    public ?bool $negotiable = null;
    public ?bool $not_available_for_credit = null;
    public ?bool $agent_cooperation = null;
    public ?bool $exchange_possible = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->hoa_fee = isset($data['hoa_fee']) ? (float)$data['hoa_fee'] : null;
        $dto->hoa_period = $data['hoa_period'] ?? null;
        $dto->price_per_sqm = isset($data['price_per_sqm']) ? (float)$data['price_per_sqm'] : null;
        $dto->annual_tax = isset($data['annual_tax']) ? (float)$data['annual_tax'] : null;
        $dto->listing_terms = $data['listing_terms'] ?? [];
        $dto->negotiable = $data['negotiable'] ?? null;
        $dto->not_available_for_credit = $data['not_available_for_credit'] ?? null;
        $dto->agent_cooperation = $data['agent_cooperation'] ?? null;
        $dto->exchange_possible = $data['exchange_possible'] ?? null;

        return $dto;
    }

    public function toArray(): array
    {
        return array_filter([
            'hoa_fee' => $this->hoa_fee,
            'hoa_period' => $this->hoa_period,
            'price_per_sqm' => $this->price_per_sqm,
            'annual_tax' => $this->annual_tax,
            'listing_terms' => $this->listing_terms,
            'negotiable' => $this->negotiable,
            'not_available_for_credit' => $this->not_available_for_credit,
            'agent_cooperation' => $this->agent_cooperation,
            'exchange_possible' => $this->exchange_possible,
        ], fn($value) => !is_null($value));
    }
}
