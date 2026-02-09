<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class VenueDto
{
    public function __construct(
        public string $tenantId,
        public string $name,
        public ?string $address,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $imageUrl,
        public bool $isActive = true,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $address = $data['address']['full_address'] ?? $data['address']['street'] ?? null;

        // The API returns images as a flat array of URL strings, not objects.
        $imageUrl = isset($data['images'][0]) && is_string($data['images'][0])
            ? $data['images'][0]
            : ($data['images'][0]['url'] ?? null);

        return new self(
            tenantId: $data['tenant_id'],
            name: $data['tenant_name'],
            address: $address,
            latitude: isset($data['address']['coordinate']) ? (float) $data['address']['coordinate']['lat'] : null,
            longitude: isset($data['address']['coordinate']) ? (float) $data['address']['coordinate']['lon'] : null,
            imageUrl: $imageUrl,
            isActive: ($data['tenant_status'] ?? 'ACTIVE') === 'ACTIVE',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'image_url' => $this->imageUrl,
        ];
    }
}
