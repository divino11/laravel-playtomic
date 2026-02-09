<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\AuthTokenDto;
use PHPUnit\Framework\TestCase;

class AuthTokenDtoTest extends TestCase
{
    public function test_from_api_response(): void
    {
        $dto = AuthTokenDto::fromApiResponse([
            'access_token' => 'abc123',
            'refresh_token' => 'def456',
            'user_id' => 'user-789',
            'access_token_expiration' => '2026-02-08T14:43:46',
        ]);

        $this->assertEquals('abc123', $dto->accessToken);
        $this->assertEquals('def456', $dto->refreshToken);
        $this->assertEquals('user-789', $dto->userId);
        $this->assertEquals('2026-02-08 14:43:46', $dto->accessTokenExpiresAt->toDateTimeString());
    }
}
