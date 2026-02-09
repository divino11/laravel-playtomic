<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\VenueDto;
use PHPUnit\Framework\TestCase;

class VenueDtoTest extends TestCase
{
    public function test_to_array(): void
    {
        $dto = new VenueDto(
            tenantId: 'abc-123',
            name: 'Test Club',
            address: '123 Test St',
            latitude: 40.0,
            longitude: -3.5,
            imageUrl: 'https://example.com/img.jpg',
        );

        $array = $dto->toArray();

        $this->assertEquals('abc-123', $array['tenant_id']);
        $this->assertEquals('Test Club', $array['name']);
        $this->assertEquals('123 Test St', $array['address']);
        $this->assertEquals(40.0, $array['latitude']);
        $this->assertEquals(-3.5, $array['longitude']);
        $this->assertEquals('https://example.com/img.jpg', $array['image_url']);
    }

    public function test_from_api_response(): void
    {
        $apiData = [
            'tenant_id' => 'xyz-789',
            'tenant_name' => 'API Club',
            'tenant_status' => 'ACTIVE',
            'address' => [
                'full_address' => '456 API Blvd',
                'coordinate' => [
                    'lat' => 41.5,
                    'lon' => 2.1,
                ],
            ],
            'images' => [
                'https://example.com/photo.jpg',
            ],
        ];

        $dto = VenueDto::fromApiResponse($apiData);

        $this->assertEquals('xyz-789', $dto->tenantId);
        $this->assertEquals('API Club', $dto->name);
        $this->assertEquals('456 API Blvd', $dto->address);
        $this->assertEquals(41.5, $dto->latitude);
        $this->assertEquals(2.1, $dto->longitude);
        $this->assertEquals('https://example.com/photo.jpg', $dto->imageUrl);
        $this->assertTrue($dto->isActive);
    }

    public function test_from_api_response_with_legacy_image_format(): void
    {
        $apiData = [
            'tenant_id' => 'xyz-789',
            'tenant_name' => 'Legacy Club',
            'address' => [
                'street' => '789 Legacy St',
            ],
            'images' => [
                ['url' => 'https://example.com/legacy.jpg'],
            ],
        ];

        $dto = VenueDto::fromApiResponse($apiData);

        $this->assertEquals('https://example.com/legacy.jpg', $dto->imageUrl);
    }

    public function test_marks_inactive_venues(): void
    {
        $apiData = [
            'tenant_id' => 'inactive-1',
            'tenant_name' => 'Closed Club',
            'tenant_status' => 'INACTIVE',
            'address' => [
                'street' => '123 Closed St',
            ],
            'images' => [],
        ];

        $dto = VenueDto::fromApiResponse($apiData);

        $this->assertFalse($dto->isActive);
    }
}
