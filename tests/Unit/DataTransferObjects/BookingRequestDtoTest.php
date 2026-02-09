<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;
use PHPUnit\Framework\TestCase;

class BookingRequestDtoTest extends TestCase
{
    public function test_start_date_time_utc_with_valid_time(): void
    {
        $dto = new BookingRequestDto(
            tenantId: 'tenant-1',
            resourceId: 'res-1',
            date: '2026-02-10',
            startTime: '09:30',
            duration: 90,
        );

        $this->assertEquals('2026-02-10T09:30:00', $dto->startDateTimeUtc());
    }

    public function test_start_date_time_utc_without_colon(): void
    {
        $dto = new BookingRequestDto(
            tenantId: 'tenant-1',
            resourceId: 'res-1',
            date: '2026-02-10',
            startTime: '0930',
            duration: 90,
        );

        $this->assertEquals('2026-02-10T00:00:00', $dto->startDateTimeUtc());
    }

    public function test_default_number_of_players(): void
    {
        $dto = new BookingRequestDto(
            tenantId: 'tenant-1',
            resourceId: 'res-1',
            date: '2026-02-10',
            startTime: '09:00',
            duration: 90,
        );

        $this->assertEquals(4, $dto->numberOfPlayers);
    }

    public function test_custom_number_of_players(): void
    {
        $dto = new BookingRequestDto(
            tenantId: 'tenant-1',
            resourceId: 'res-1',
            date: '2026-02-10',
            startTime: '09:00',
            duration: 90,
            numberOfPlayers: 2,
        );

        $this->assertEquals(2, $dto->numberOfPlayers);
    }
}
