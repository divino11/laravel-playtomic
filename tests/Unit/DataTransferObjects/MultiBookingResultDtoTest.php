<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;
use Divino11\Playtomic\DataTransferObjects\BookingResultDto;
use Divino11\Playtomic\DataTransferObjects\MultiBookingResultDto;
use PHPUnit\Framework\TestCase;

class MultiBookingResultDtoTest extends TestCase
{
    public function test_all_succeeded_when_no_failures(): void
    {
        $dto = new MultiBookingResultDto(
            successful: [
                new BookingResultDto('m-1', 'CONFIRMED', 'Court 1', '2026-02-10', '09:00', 90, '€15'),
                new BookingResultDto('m-2', 'CONFIRMED', 'Court 2', '2026-02-10', '09:00', 90, '€15'),
            ],
            failed: [],
            allSucceeded: true,
        );

        $this->assertTrue($dto->allSucceeded);
        $this->assertCount(2, $dto->successful);
        $this->assertCount(0, $dto->failed);
    }

    public function test_not_all_succeeded_when_there_are_failures(): void
    {
        $dto = new MultiBookingResultDto(
            successful: [
                new BookingResultDto('m-1', 'CONFIRMED', 'Court 1', '2026-02-10', '09:00', 90, '€15'),
            ],
            failed: [
                new BookingRequestDto('tenant-1', 'res-2', '2026-02-10', '09:00', 90),
            ],
            allSucceeded: false,
        );

        $this->assertFalse($dto->allSucceeded);
        $this->assertCount(1, $dto->successful);
        $this->assertCount(1, $dto->failed);
    }

    public function test_empty_result(): void
    {
        $dto = new MultiBookingResultDto(
            successful: [],
            failed: [],
            allSucceeded: true,
        );

        $this->assertTrue($dto->allSucceeded);
        $this->assertCount(0, $dto->successful);
        $this->assertCount(0, $dto->failed);
    }

    public function test_to_array(): void
    {
        $dto = new MultiBookingResultDto(
            successful: [
                new BookingResultDto('m-1', 'CONFIRMED', 'Court 1', '2026-02-10', '09:00', 90, '€15'),
            ],
            failed: [
                new BookingRequestDto('tenant-1', 'res-2', '2026-02-10', '10:00', 60),
            ],
            allSucceeded: false,
        );

        $array = $dto->toArray();

        $this->assertFalse($array['all_succeeded']);
        $this->assertCount(1, $array['successful']);
        $this->assertCount(1, $array['failed']);
        $this->assertEquals('m-1', $array['successful'][0]['match_id']);
        $this->assertEquals('res-2', $array['failed'][0]['resource_id']);
        $this->assertEquals('tenant-1', $array['failed'][0]['tenant_id']);
        $this->assertEquals('2026-02-10', $array['failed'][0]['date']);
        $this->assertEquals('10:00', $array['failed'][0]['start_time']);
        $this->assertEquals(60, $array['failed'][0]['duration']);
    }
}
