<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\BookingResultDto;
use PHPUnit\Framework\TestCase;

class BookingResultDtoTest extends TestCase
{
    public function test_from_api_response_with_cart_data(): void
    {
        $data = [
            'status' => 'CONFIRMED',
            'payment_intent_id' => 'pi-123',
            'cart' => [
                'item' => [
                    'cart_item_data' => [
                        'match_id' => 'match-abc',
                        'start' => '2026-02-10T09:00:00',
                        'duration' => 90,
                    ],
                ],
            ],
            'resource' => ['name' => 'Court 1'],
            'price' => '15.00',
        ];

        $dto = BookingResultDto::fromApiResponse($data);

        $this->assertEquals('match-abc', $dto->matchId);
        $this->assertEquals('CONFIRMED', $dto->status);
        $this->assertEquals('Court 1', $dto->courtName);
        $this->assertEquals('2026-02-10', $dto->startDate);
        $this->assertEquals('09:00', $dto->startTime);
        $this->assertEquals(90, $dto->duration);
        $this->assertEquals('15.00', $dto->price);
    }

    public function test_to_array(): void
    {
        $dto = new BookingResultDto(
            matchId: 'match-1',
            status: 'CONFIRMED',
            courtName: 'Court A',
            startDate: '2026-02-10',
            startTime: '10:00',
            duration: 60,
            price: '€12.00',
        );

        $array = $dto->toArray();

        $this->assertEquals('match-1', $array['match_id']);
        $this->assertEquals('CONFIRMED', $array['status']);
        $this->assertEquals('Court A', $array['court_name']);
        $this->assertEquals('€12.00', $array['price']);
    }
}
