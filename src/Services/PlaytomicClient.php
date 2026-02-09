<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Services;

use Divino11\Playtomic\Contracts\PlaytomicAuthClientInterface;
use Divino11\Playtomic\Contracts\PlaytomicClientInterface;
use Divino11\Playtomic\DataTransferObjects\AuthTokenDto;
use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;
use Divino11\Playtomic\DataTransferObjects\BookingResultDto;
use Divino11\Playtomic\DataTransferObjects\CourtSlotDto;
use Divino11\Playtomic\DataTransferObjects\PaymentIntentResponseDto;
use Divino11\Playtomic\DataTransferObjects\UserBookingDto;
use Divino11\Playtomic\DataTransferObjects\VenueDto;
use Divino11\Playtomic\Exceptions\GeocodingException;
use Divino11\Playtomic\Exceptions\PlaytomicApiException;
use Divino11\Playtomic\Exceptions\PlaytomicAuthException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaytomicClient implements PlaytomicAuthClientInterface, PlaytomicClientInterface
{
    private string $baseUrl;

    private int $timeout;

    private int $defaultRadius;

    public function __construct()
    {
        $this->baseUrl = config('playtomic.base_url', 'https://api.playtomic.io/v1');
        $this->timeout = (int) config('playtomic.timeout', 15);
        $this->defaultRadius = (int) config('playtomic.default_radius', 50000);
    }

    /**
     * @return VenueDto[]
     */
    public function searchVenues(float $latitude, float $longitude, int $radius): array
    {
        $cacheKey = sprintf('playtomic:venues:%.4f:%.4f:%d', $latitude, $longitude, $radius);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($latitude, $longitude, $radius) {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildHeaders())
                ->get("{$this->baseUrl}/tenants", [
                    'sport_id' => 'PADEL',
                    'coordinate' => sprintf('%.6f,%.6f', $latitude, $longitude),
                    'radius' => $radius,
                ]);

            if ($response->failed()) {
                throw new PlaytomicApiException(
                    "Venue search failed: {$response->status()} {$response->body()}"
                );
            }

            $venues = array_map(
                fn(array $venue) => VenueDto::fromApiResponse($venue),
                $response->json()
            );

            return array_values(array_filter(
                $venues,
                fn(VenueDto $venue) => $venue->isActive,
            ));
        });
    }

    /**
     * @return CourtSlotDto[]
     */
    public function getAvailability(string $tenantId, string $date): array
    {
        $cacheKey = "playtomic:availability:{$tenantId}:{$date}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId, $date) {
            $resourceMap = $this->getResourceMap($tenantId);

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildHeaders())
                ->get("{$this->baseUrl}/availability", [
                    'sport_id' => 'PADEL',
                    'tenant_id' => $tenantId,
                    'start_min' => "{$date}T00:00:00",
                    'start_max' => "{$date}T23:59:59",
                ]);

            if ($response->failed()) {
                throw new PlaytomicApiException(
                    "Availability check failed: {$response->status()} {$response->body()}"
                );
            }

            $slots = [];

            foreach ($response->json() as $resource) {
                $resourceId = $resource['resource_id'];
                $courtName = $resourceMap[$resourceId] ?? "Court {$resourceId}";
                $startDate = $resource['start_date'];

                foreach ($resource['slots'] as $slot) {
                    $slots[] = CourtSlotDto::fromApiResponse($resourceId, $courtName, $startDate, $slot);
                }
            }

            return $slots;
        });
    }

    public function login(string $email, string $password): AuthTokenDto
    {
        $authBaseUrl = str_replace('/v1', '/v3', $this->baseUrl);

        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->post("{$authBaseUrl}/auth/login", [
                'grant_type' => 'password',
                'email' => $email,
                'password' => $password,
            ]);

        if ($response->failed()) {
            $message = $response->json('localized_message')
                ?? 'Invalid Playtomic credentials. Please check your email and password.';

            throw new PlaytomicAuthException($message);
        }

        return AuthTokenDto::fromApiResponse($response->json());
    }

    public function refreshToken(string $refreshToken): AuthTokenDto
    {
        $authBaseUrl = str_replace('/v1', '/v3', $this->baseUrl);

        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->post("{$authBaseUrl}/auth/token", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if ($response->failed()) {
            throw new PlaytomicAuthException(
                'Failed to refresh Playtomic token. Please re-link your account.'
            );
        }

        return AuthTokenDto::fromApiResponse($response->json());
    }

    public function createPaymentIntent(string $accessToken, BookingRequestDto $request, string $userId): PaymentIntentResponseDto
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->withToken($accessToken)
            ->post("{$this->baseUrl}/payment_intents", [
                'allowed_payment_method_types' => [
                    'OFFER',
                    'CASH',
                    'MERCHANT_WALLET',
                    'DIRECT',
                    'SWISH',
                    'IDEAL',
                    'BANCONTACT',
                    'PAYTRAIL',
                    'CREDIT_CARD',
                    'QUICK_PAY',
                ],
                'user_id' => $userId,
                'cart' => [
                    'requested_item' => [
                        'cart_item_type' => 'CUSTOMER_MATCH',
                        'cart_item_voucher_id' => null,
                        'cart_item_data' => [
                            'supports_split_payment' => true,
                            'number_of_players' => $request->numberOfPlayers,
                            'tenant_id' => $request->tenantId,
                            'resource_id' => $request->resourceId,
                            'start' => $request->startDateTimeUtc(),
                            'duration' => $request->duration,
                            'match_registrations' => [
                                ['user_id' => $userId, 'pay_now' => true],
                            ],
                        ],
                    ],
                ],
            ]);

        Log::info('Playtomic create payment intent response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->status() === 401) {
            throw new PlaytomicAuthException('Playtomic session expired. Please re-link your account.');
        }

        if ($response->failed()) {
            throw new PlaytomicApiException(
                "Payment intent creation failed: {$response->status()} {$response->body()}"
            );
        }

        $result = PaymentIntentResponseDto::fromApiResponse($response->json());

        if ($result->paymentIntentId === '') {
            throw new PlaytomicApiException('Payment intent response missing ID.');
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function updatePaymentIntent(string $accessToken, string $paymentIntentId, string $paymentMethod): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->withToken($accessToken)
            ->patch("{$this->baseUrl}/payment_intents/{$paymentIntentId}", [
                'selected_payment_method_id' => $paymentMethod,
            ]);

        Log::info('Playtomic update payment intent response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'payment_intent_id' => $paymentIntentId,
            'payment_method' => $paymentMethod,
        ]);

        if ($response->status() === 401) {
            throw new PlaytomicAuthException('Playtomic session expired. Please re-link your account.');
        }

        if ($response->failed()) {
            throw new PlaytomicApiException(
                "Payment intent update failed: {$response->status()} {$response->body()}"
            );
        }

        return $response->json();
    }

    public function confirmPaymentIntent(string $accessToken, string $paymentIntentId): BookingResultDto
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->withToken($accessToken)
            ->post("{$this->baseUrl}/payment_intents/{$paymentIntentId}/confirmation");

        Log::info('Playtomic confirm payment intent response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'payment_intent_id' => $paymentIntentId,
        ]);

        if ($response->status() === 401) {
            throw new PlaytomicAuthException('Playtomic session expired. Please re-link your account.');
        }

        // After Stripe payment, Playtomic may auto-confirm the booking.
        // The confirmation call then returns 422 PAYMENT_INTENT_STATUS_INVALID
        // because the intent is already completed. Treat this as success.
        if ($response->status() === 422) {
            $body = $response->json();

            if (($body['status'] ?? '') === 'PAYMENT_INTENT_STATUS_INVALID') {
                Log::info('Payment intent already confirmed (auto-confirmed after Stripe payment)', [
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return new BookingResultDto(
                    matchId: $paymentIntentId,
                    status: 'CONFIRMED',
                    courtName: '',
                    startDate: '',
                    startTime: '',
                    duration: 0,
                    price: '',
                );
            }
        }

        if ($response->failed()) {
            throw new PlaytomicApiException(
                "Payment intent confirmation failed: {$response->status()} {$response->body()}"
            );
        }

        return BookingResultDto::fromApiResponse($response->json());
    }

    /**
     * Fetch the user's matches (bookings) from Playtomic.
     *
     * @return UserBookingDto[]
     */
    public function getUserMatches(string $accessToken, string $userId, int $size = 50, string $sort = 'start_date,DESC'): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->withToken($accessToken)
            ->get("{$this->baseUrl}/matches", [
                'owner_id' => $userId,
                'size' => $size,
                'sort' => $sort,
            ]);

        if ($response->status() === 401) {
            throw new PlaytomicAuthException('Playtomic session expired. Please re-link your account.');
        }

        if ($response->failed()) {
            throw new PlaytomicApiException(
                "Failed to fetch user matches: {$response->status()} {$response->body()}"
            );
        }

        return array_map(
            fn(array $match) => UserBookingDto::fromApiResponse($match),
            $response->json() ?? [],
        );
    }

    /**
     * Cancel a booking (match) on Playtomic.
     */
    public function cancelBooking(string $accessToken, string $matchId, string $reasonCode = 'CANCELED_BY_OWNER'): void
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->buildHeaders())
            ->withToken($accessToken)
            ->post("{$this->baseUrl}/matches/{$matchId}/cancellation", [
                'cancellation_reason_code' => $reasonCode,
            ]);

        Log::info('Playtomic cancel booking response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'match_id' => $matchId,
            'reason_code' => $reasonCode,
        ]);

        if ($response->status() === 401) {
            throw new PlaytomicAuthException('Playtomic session expired. Please re-link your account.');
        }

        if ($response->failed()) {
            throw new PlaytomicApiException(
                "Booking cancellation failed: {$response->status()} {$response->body()}"
            );
        }
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    public function geocode(string $location): array
    {
        $cacheKey = 'playtomic:geocode:' . md5(strtolower(trim($location)));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($location) {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => 'LaravelPlaytomic/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'limit' => 1,
                    'q' => $location,
                ]);

            if ($response->failed() || empty($response->json())) {
                throw new GeocodingException("Could not find location: {$location}");
            }

            $result = $response->json()[0];

            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
            ];
        });
    }

    /**
     * Fetch the resource (court) names for a venue.
     *
     * @return array<string, string>
     */
    private function getResourceMap(string $tenantId): array
    {
        $cacheKey = "playtomic:resources:{$tenantId}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($tenantId) {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->buildHeaders())
                ->get("{$this->baseUrl}/tenants/{$tenantId}/resources");

            if ($response->failed()) {
                return [];
            }

            $map = [];

            foreach ($response->json() as $resource) {
                $map[$resource['resource_id']] = $resource['name'] ?? "Court {$resource['resource_id']}";
            }

            return $map;
        });
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            'User-Agent' => 'Android 11',
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'com.playtomic.app 6.59.0',
        ];
    }
}
