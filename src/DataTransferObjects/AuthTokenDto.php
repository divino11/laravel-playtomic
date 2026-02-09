<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

use Carbon\Carbon;

readonly class AuthTokenDto
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $userId,
        public Carbon $accessTokenExpiresAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'],
            userId: $data['user_id'],
            accessTokenExpiresAt: Carbon::parse($data['access_token_expiration']),
        );
    }
}
