<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class UserBookingDto
{
    public function __construct(
        public string $matchId,
        public string $startDate,
        public string $endDate,
        public string $status,
        public string $resourceName,
        public string $price,
        public string $tenantName,
        public string $tenantCity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            matchId: $data['match_id'] ?? '',
            startDate: $data['start_date'] ?? '',
            endDate: $data['end_date'] ?? '',
            status: $data['status'] ?? '',
            resourceName: $data['resource_name'] ?? '',
            price: $data['price'] ?? '',
            tenantName: $data['tenant']['tenant_name'] ?? '',
            tenantCity: $data['tenant']['address']['city'] ?? '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'match_id' => $this->matchId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => $this->status,
            'resource_name' => $this->resourceName,
            'price' => $this->price,
            'tenant_name' => $this->tenantName,
            'tenant_city' => $this->tenantCity,
        ];
    }
}
