<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class MultiBookingResultDto
{
    /**
     * @param  BookingResultDto[]  $successful  Successfully booked courts.
     * @param  BookingRequestDto[]  $failed  Requests that failed to book.
     */
    public function __construct(
        public array $successful,
        public array $failed,
        public bool $allSucceeded,
    ) {
    }

    /**
     * @return array{successful: list<array<string, mixed>>, failed: list<array<string, mixed>>, all_succeeded: bool}
     */
    public function toArray(): array
    {
        return [
            'successful' => array_map(
                fn(BookingResultDto $result) => $result->toArray(),
                $this->successful,
            ),
            'failed' => array_map(
                fn(BookingRequestDto $request) => [
                    'tenant_id' => $request->tenantId,
                    'resource_id' => $request->resourceId,
                    'date' => $request->date,
                    'start_time' => $request->startTime,
                    'duration' => $request->duration,
                ],
                $this->failed,
            ),
            'all_succeeded' => $this->allSucceeded,
        ];
    }
}
