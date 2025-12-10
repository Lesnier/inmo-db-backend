<?php

namespace App\DTOs\Property;

class PriceHistoryEntry
{
    public string $date;
    public string $event;
    public float $price;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->date = $data['date'] ?? '';
        $dto->event = $data['event'] ?? '';
        $dto->price = (float)($data['price'] ?? 0.0);
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'event' => $this->event,
            'price' => $this->price,
        ];
    }
}
