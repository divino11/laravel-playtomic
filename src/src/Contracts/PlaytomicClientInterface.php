<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Contracts;

use Divino11\Playtomic\DataTransferObjects\CourtSlotDto;
use Divino11\Playtomic\DataTransferObjects\VenueDto;

interface PlaytomicClientInterface
{
    /**
     * Search for padel venues near given coordinates.
     *
     * @return VenueDto[]
     */
    public function searchVenues(float $latitude, float $longitude, int $radius): array;

    /**
     * Get court availability for a venue on a specific date.
     *
     * @return CourtSlotDto[]
     */
    public function getAvailability(string $tenantId, string $date): array;

    /**
     * Geocode a location string to coordinates.
     *
     * @return array{latitude: float, longitude: float}
     */
    public function geocode(string $location): array;
}
