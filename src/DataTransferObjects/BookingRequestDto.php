<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class BookingRequestDto
{
    public function __construct(
        public string $tenantId,
        public string $resourceId,
        public string $date,
        public string $startTime,
        public int $duration,
        public int $numberOfPlayers = 4,
    ) {
    }

    /**
     * Build the UTC start datetime string for the Payment Intent API.
     *
     * Combines date (YYYY-MM-DD) and startTime (HH:MM) into "YYYY-MM-DDTHH:MM:00".
     */
    public function startDateTimeUtc(): string
    {
        $time = str_contains($this->startTime, ':')
            ? $this->startTime
            : '00:00';

        return sprintf('%sT%s:00', $this->date, substr($time, 0, 5));
    }
}
