# Laravel Playtomic

A Laravel SDK for the [Playtomic](https://playtomic.io) API — venue search, court availability, booking, and authentication.

## Installation

```bash
composer require divino11/laravel-playtomic
```

Laravel will auto-discover the service provider. To publish the configuration file:

```bash
php artisan vendor:publish --tag=playtomic-config
```

## Configuration

Set the following environment variables (all optional — sensible defaults are provided):

| Variable | Default | Description |
|---|---|---|
| `PLAYTOMIC_BASE_URL` | `https://api.playtomic.io/v1` | API base URL |
| `PLAYTOMIC_TIMEOUT` | `15` | HTTP timeout in seconds |
| `PLAYTOMIC_DEFAULT_RADIUS` | `50000` | Venue search radius in meters |

## Usage

### Search Venues

```php
use Divino11\Playtomic\Contracts\PlaytomicClientInterface;

$client = app(PlaytomicClientInterface::class);
$venues = $client->searchVenues(40.4168, -3.7038, 50000);
```

### Check Availability

```php
$slots = $client->getAvailability('tenant-id', '2026-02-10');
```

### Authentication (for bookings)

```php
use Divino11\Playtomic\Contracts\PlaytomicAuthClientInterface;

$authClient = app(PlaytomicAuthClientInterface::class);
$tokens = $authClient->login('user@example.com', 'password');
```

### High-Level Service

```php
use Divino11\Playtomic\Services\PlaytomicService;

$service = app(PlaytomicService::class);

// Search by location name (geocodes automatically)
$venues = $service->searchVenuesByLocation('Madrid');

// Get availability grouped by time slot
$grouped = $service->getGroupedAvailability('tenant-id', '2026-02-10');
```

### Book a Court

Booking requires authentication. The flow is: **create → update → confirm** a payment intent.

```php
use Divino11\Playtomic\Contracts\PlaytomicAuthClientInterface;
use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;

$authClient = app(PlaytomicAuthClientInterface::class);
$tokens = $authClient->login('user@example.com', 'password');

// 1. Create a payment intent
$request = new BookingRequestDto(
    tenantId: 'tenant-uuid',
    resourceId: 'court-resource-uuid',
    date: '2026-02-10',
    startTime: '10:00',
    duration: 90,           // minutes
    numberOfPlayers: 4,     // optional, defaults to 4
);

$paymentIntent = $authClient->createPaymentIntent(
    $tokens->accessToken,
    $request,
    $tokens->userId,
);

// 2. Update with payment method
$authClient->updatePaymentIntent(
    $tokens->accessToken,
    $paymentIntent->paymentIntentId,
    'WALLET',  // payment method
);

// 3. Confirm the booking
$result = $authClient->confirmPaymentIntent(
    $tokens->accessToken,
    $paymentIntent->paymentIntentId,
);

// $result is a BookingResultDto with: matchId, status, courtName, startDate, startTime, duration, price
```

### Get User Bookings

```php
$bookings = $authClient->getUserMatches(
    $tokens->accessToken,
    $tokens->userId,
    size: 50,                    // optional, defaults to 50
    sort: 'start_date,DESC',    // optional
);

// Each booking is a UserBookingDto with: matchId, startDate, endDate, status, resourceName, price, tenantName, tenantCity
```

### Cancel a Booking

```php
// Cancel with default reason ('PERSONAL')
$authClient->cancelBooking($tokens->accessToken, 'match-id');

// Cancel with a specific reason code
$authClient->cancelBooking($tokens->accessToken, 'match-id', 'CANCELED_BY_OWNER');
```

### Book Multiple Courts

Book several courts at once with automatic rollback if any booking fails:

```php
use Divino11\Playtomic\Contracts\PlaytomicAuthClientInterface;
use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;

$authClient = app(PlaytomicAuthClientInterface::class);
$tokens = $authClient->login('user@example.com', 'password');

$requests = [
    new BookingRequestDto('tenant-uuid', 'court-1-uuid', '2026-02-10', '10:00', 90),
    new BookingRequestDto('tenant-uuid', 'court-2-uuid', '2026-02-10', '10:00', 90),
    new BookingRequestDto('tenant-uuid', 'court-3-uuid', '2026-02-10', '10:00', 90),
];

// Books each court sequentially, cancels all if one fails
$result = $authClient->bookMultipleCourts(
    $tokens->accessToken,
    $requests,
    $tokens->userId,
    rollbackOnFailure: true, // set to false to keep successful bookings even if one fails
);

// $result is a MultiBookingResultDto
$result->allSucceeded;  // bool
$result->successful;    // BookingResultDto[]
$result->failed;        // BookingRequestDto[]
```

## Testing

```bash
composer test
```

## License

MIT
