<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class CourtSlotDto
{
    public function __construct(
        public string $resourceId,
        public string $courtName,
        public string $startDate,
        public string $startTime,
        public int $duration,
        public string $price,
    ) {
    }

    /**
     * @param  array<string, mixed>  $slotData
     */
    public static function fromApiResponse(string $resourceId, string $courtName, string $startDate, array $slotData): self
    {
        return new self(
            resourceId: $resourceId,
            courtName: $courtName,
            startDate: $startDate,
            startTime: $slotData['start_time'],
            duration: (int) $slotData['duration'],
            price: $slotData['price'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resource_id' => $this->resourceId,
            'court_name' => $this->courtName,
            'start_date' => $this->startDate,
            'start_time' => $this->startTime,
            'duration' => $this->duration,
            'price' => $this->price,
        ];
    }
}
