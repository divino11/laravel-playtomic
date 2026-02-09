<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\Services;

use Divino11\Playtomic\Contracts\PlaytomicClientInterface;
use Divino11\Playtomic\DataTransferObjects\CourtSlotDto;
use Divino11\Playtomic\Services\PlaytomicService;
use PHPUnit\Framework\TestCase;

class PlaytomicServiceTest extends TestCase
{
    public function test_get_grouped_availability_groups_by_time(): void
    {
        $mockClient = $this->createMock(PlaytomicClientInterface::class);
        $mockClient->expects($this->once())
            ->method('getAvailability')
            ->with('tenant-123', '2026-02-10')
            ->willReturn([
                new CourtSlotDto('res-1', 'Court 1', '2026-02-10', '09:00', 90, '€15.00'),
                new CourtSlotDto('res-2', 'Court 2', '2026-02-10', '09:00', 60, '€12.00'),
                new CourtSlotDto('res-1', 'Court 1', '2026-02-10', '10:30', 90, '€15.00'),
            ]);

        $service = new PlaytomicService($mockClient);
        $grouped = $service->getGroupedAvailability('tenant-123', '2026-02-10');

        $this->assertCount(2, $grouped);

        // 09:00 slot has 2 courts
        $this->assertEquals('09:00', $grouped[0]['start_time']);
        $this->assertCount(2, $grouped[0]['courts']);
        $this->assertEquals('Court 1', $grouped[0]['courts'][0]['court_name']);
        $this->assertEquals('Court 2', $grouped[0]['courts'][1]['court_name']);

        // 10:30 slot has 1 court
        $this->assertEquals('10:30', $grouped[1]['start_time']);
        $this->assertCount(1, $grouped[1]['courts']);
        $this->assertEquals('Court 1', $grouped[1]['courts'][0]['court_name']);
    }

    public function test_get_grouped_availability_returns_empty_for_no_slots(): void
    {
        $mockClient = $this->createMock(PlaytomicClientInterface::class);
        $mockClient->expects($this->once())
            ->method('getAvailability')
            ->willReturn([]);

        $service = new PlaytomicService($mockClient);
        $grouped = $service->getGroupedAvailability('tenant-123', '2026-02-10');

        $this->assertEquals([], $grouped);
    }

    public function test_get_grouped_availability_sorts_by_time(): void
    {
        $mockClient = $this->createMock(PlaytomicClientInterface::class);
        $mockClient->expects($this->once())
            ->method('getAvailability')
            ->willReturn([
                new CourtSlotDto('res-1', 'Court 1', '2026-02-10', '14:00', 90, '€15.00'),
                new CourtSlotDto('res-1', 'Court 1', '2026-02-10', '09:00', 90, '€15.00'),
                new CourtSlotDto('res-1', 'Court 1', '2026-02-10', '11:00', 90, '€15.00'),
            ]);

        $service = new PlaytomicService($mockClient);
        $grouped = $service->getGroupedAvailability('tenant-123', '2026-02-10');

        $this->assertEquals('09:00', $grouped[0]['start_time']);
        $this->assertEquals('11:00', $grouped[1]['start_time']);
        $this->assertEquals('14:00', $grouped[2]['start_time']);
    }
}
