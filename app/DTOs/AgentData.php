<?php

namespace App\DTOs;

class AgentData
{
    public function __construct(
        public ?string $company_name = null,
        public ?string $license_number = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zip_code = null,
        public ?string $avatar = null,
        public ?string $bio = null,
        public ?array $services = [],
        public ?array $specialties = [],
        public ?array $languages = [],
        public ?int $properties_limit = 10,
        public ?int $leads_limit = 50,
        public ?array $features = [],
        public ?array $social_media = [],
        public ?array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'license_number' => $this->license_number,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'services' => $this->services,
            'specialties' => $this->specialties,
            'languages' => $this->languages,
            'properties_limit' => $this->properties_limit,
            'leads_limit' => $this->leads_limit,
            'features' => $this->features,
            'social_media' => $this->social_media,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            company_name: $data['company_name'] ?? null,
            license_number: $data['license_number'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            zip_code: $data['zip_code'] ?? null,
            avatar: $data['avatar'] ?? null,
            bio: $data['bio'] ?? null,
            services: $data['services'] ?? [],
            specialties: $data['specialties'] ?? [],
            languages: $data['languages'] ?? [],
            properties_limit: $data['properties_limit'] ?? 10,
            leads_limit: $data['leads_limit'] ?? 50,
            features: $data['features'] ?? [],
            social_media: $data['social_media'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }
}
