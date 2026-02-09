<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Services;

use Divino11\Playtomic\Contracts\PlaytomicClientInterface;
use Divino11\Playtomic\DataTransferObjects\VenueDto;

class PlaytomicService
{
    public function __construct(
        private PlaytomicClientInterface $client,
    ) {}

    /**
     * Search venues near a location string.
     *
     * @return VenueDto[]
     */
    public function searchVenuesByLocation(string $location): array
    {
        $coordinates = $this->client->geocode($location);

        $radius = (int) config('playtomic.default_radius', 50000);

        return $this->client->searchVenues(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $radius,
        );
    }

    /**
     * Get availability for a venue on a specific date, grouped by time slot.
     *
     * Each time slot shows all courts available at that time, making it
     * easy to see how many courts can be booked simultaneously.
     *
     * @return list<array{start_time: string, courts: list<array<string, mixed>>}>
     */
    public function getGroupedAvailability(string $tenantId, string $date): array
    {
        $slots = $this->client->getAvailability($tenantId, $date);

        $grouped = [];

        foreach ($slots as $slot) {
            $timeKey = $slot->startTime;

            if (! isset($grouped[$timeKey])) {
                $grouped[$timeKey] = [
                    'start_time' => $slot->startTime,
                    'courts' => [],
                ];
            }

            $grouped[$timeKey]['courts'][] = [
                'court_name' => $slot->courtName,
                'resource_id' => $slot->resourceId,
                'duration' => $slot->duration,
                'price' => $slot->price,
            ];
        }

        ksort($grouped);

        return array_values($grouped);
    }
}
