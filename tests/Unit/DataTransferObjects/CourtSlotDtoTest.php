<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\CourtSlotDto;
use PHPUnit\Framework\TestCase;

class CourtSlotDtoTest extends TestCase
{
    public function test_to_array(): void
    {
        $dto = new CourtSlotDto(
            resourceId: 'res-1',
            courtName: 'Court A',
            startDate: '2026-02-10',
            startTime: '09:30',
            duration: 90,
            price: '€18.00',
        );

        $array = $dto->toArray();

        $this->assertEquals('res-1', $array['resource_id']);
        $this->assertEquals('Court A', $array['court_name']);
        $this->assertEquals('2026-02-10', $array['start_date']);
        $this->assertEquals('09:30', $array['start_time']);
        $this->assertEquals(90, $array['duration']);
        $this->assertEquals('€18.00', $array['price']);
    }

    public function test_from_api_response(): void
    {
        $slotData = [
            'start_time' => '14:00',
            'duration' => 60,
            'price' => '€10.00',
        ];

        $dto = CourtSlotDto::fromApiResponse('res-2', 'Court B', '2026-02-10', $slotData);

        $this->assertEquals('res-2', $dto->resourceId);
        $this->assertEquals('Court B', $dto->courtName);
        $this->assertEquals('2026-02-10', $dto->startDate);
        $this->assertEquals('14:00', $dto->startTime);
        $this->assertEquals(60, $dto->duration);
        $this->assertEquals('€10.00', $dto->price);
    }
}
