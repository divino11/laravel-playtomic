<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Contracts;

use Divino11\Playtomic\DataTransferObjects\AuthTokenDto;
use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;
use Divino11\Playtomic\DataTransferObjects\BookingResultDto;
use Divino11\Playtomic\DataTransferObjects\PaymentIntentResponseDto;
use Divino11\Playtomic\DataTransferObjects\UserBookingDto;

interface PlaytomicAuthClientInterface
{
    /**
     * Authenticate with Playtomic using email and password.
     */
    public function login(string $email, string $password): AuthTokenDto;

    /**
     * Refresh an expired access token.
     */
    public function refreshToken(string $refreshToken): AuthTokenDto;

    /**
     * Create a payment intent for booking a court.
     */
    public function createPaymentIntent(string $accessToken, BookingRequestDto $request, string $userId): PaymentIntentResponseDto;

    /**
     * Update a payment intent with the selected payment method.
     *
     * @return array<string, mixed>
     */
    public function updatePaymentIntent(string $accessToken, string $paymentIntentId, string $paymentMethod): array;

    /**
     * Confirm a payment intent to finalize the booking.
     */
    public function confirmPaymentIntent(string $accessToken, string $paymentIntentId): BookingResultDto;

    /**
     * Fetch the user's matches (bookings) from Playtomic.
     *
     * @return UserBookingDto[]
     */
    public function getUserMatches(string $accessToken, string $userId, int $size = 50, string $sort = 'start_date,DESC'): array;
}
