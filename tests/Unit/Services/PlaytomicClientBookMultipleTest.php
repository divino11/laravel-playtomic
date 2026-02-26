<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\Services;

use Divino11\Playtomic\DataTransferObjects\BookingRequestDto;
use Divino11\Playtomic\DataTransferObjects\BookingResultDto;
use Divino11\Playtomic\DataTransferObjects\PaymentIntentResponseDto;
use Divino11\Playtomic\Services\PlaytomicClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class PlaytomicClientBookMultipleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Divino11\Playtomic\PlaytomicServiceProvider::class];
    }

    private function buildPaymentIntentResponse(string $intentId): array
    {
        return [
            'payment_intent_id' => $intentId,
            'available_payment_methods' => [
                ['type' => 'CASH'],
                ['type' => 'CREDIT_CARD'],
            ],
        ];
    }

    private function buildUpdateResponse(): array
    {
        return [
            'status' => 'PENDING',
            'selected_payment_method_id' => 'CASH',
        ];
    }

    private function buildConfirmResponse(string $matchId): array
    {
        return [
            'status' => 'CONFIRMED',
            'payment_intent_id' => $matchId,
            'cart' => [
                'item' => [
                    'cart_item_data' => [
                        'match_id' => $matchId,
                        'start' => '2026-02-10T09:00:00',
                        'duration' => 90,
                    ],
                ],
            ],
            'resource' => ['name' => 'Court'],
            'price' => '15.00',
        ];
    }

    public function test_book_multiple_courts_all_succeed(): void
    {
        Http::fake([
            '*/payment_intents' => Http::sequence()
                ->push($this->buildPaymentIntentResponse('pi-1'))
                ->push($this->buildPaymentIntentResponse('pi-2'))
                ->push($this->buildPaymentIntentResponse('pi-3')),
            '*/payment_intents/pi-1' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-2' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-3' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-1/confirmation' => Http::response($this->buildConfirmResponse('match-1')),
            '*/payment_intents/pi-2/confirmation' => Http::response($this->buildConfirmResponse('match-2')),
            '*/payment_intents/pi-3/confirmation' => Http::response($this->buildConfirmResponse('match-3')),
        ]);

        $client = new PlaytomicClient();
        $requests = [
            new BookingRequestDto('tenant-1', 'res-1', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-2', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-3', '2026-02-10', '09:00', 90),
        ];

        $result = $client->bookMultipleCourts('token-123', $requests, 'user-1');

        $this->assertTrue($result->allSucceeded);
        $this->assertCount(3, $result->successful);
        $this->assertCount(0, $result->failed);
        $this->assertEquals('match-1', $result->successful[0]->matchId);
        $this->assertEquals('match-2', $result->successful[1]->matchId);
        $this->assertEquals('match-3', $result->successful[2]->matchId);
    }

    public function test_second_court_fails_triggers_rollback(): void
    {
        Http::fake([
            '*/payment_intents' => Http::sequence()
                ->push($this->buildPaymentIntentResponse('pi-1'))
                ->push([], 500),
            '*/payment_intents/pi-1' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-1/confirmation' => Http::response($this->buildConfirmResponse('match-1')),
            '*/matches/match-1/cancellation' => Http::response([], 200),
        ]);

        $client = new PlaytomicClient();
        $requests = [
            new BookingRequestDto('tenant-1', 'res-1', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-2', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-3', '2026-02-10', '09:00', 90),
        ];

        $result = $client->bookMultipleCourts('token-123', $requests, 'user-1', rollbackOnFailure: true);

        $this->assertFalse($result->allSucceeded);
        $this->assertCount(0, $result->successful); // rolled back
        $this->assertCount(2, $result->failed); // 2nd failed + 3rd remaining

        // Verify cancellation was called
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'matches/match-1/cancellation');
        });
    }

    public function test_second_court_fails_without_rollback_keeps_first(): void
    {
        Http::fake([
            '*/payment_intents' => Http::sequence()
                ->push($this->buildPaymentIntentResponse('pi-1'))
                ->push([], 500),
            '*/payment_intents/pi-1' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-1/confirmation' => Http::response($this->buildConfirmResponse('match-1')),
        ]);

        $client = new PlaytomicClient();
        $requests = [
            new BookingRequestDto('tenant-1', 'res-1', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-2', '2026-02-10', '09:00', 90),
            new BookingRequestDto('tenant-1', 'res-3', '2026-02-10', '09:00', 90),
        ];

        $result = $client->bookMultipleCourts('token-123', $requests, 'user-1', rollbackOnFailure: false);

        $this->assertFalse($result->allSucceeded);
        $this->assertCount(1, $result->successful); // kept
        $this->assertCount(2, $result->failed); // 2nd failed + 3rd remaining
        $this->assertEquals('match-1', $result->successful[0]->matchId);

        // Verify no cancellation was called
        Http::assertNotSent(function (Request $request) {
            return str_contains($request->url(), 'cancellation');
        });
    }

    public function test_empty_requests_returns_empty_result(): void
    {
        Http::fake();

        $client = new PlaytomicClient();
        $result = $client->bookMultipleCourts('token-123', [], 'user-1');

        $this->assertTrue($result->allSucceeded);
        $this->assertCount(0, $result->successful);
        $this->assertCount(0, $result->failed);

        // No HTTP calls should have been made
        Http::assertNothingSent();
    }

    public function test_stripe_required_triggers_failure(): void
    {
        Http::fake([
            '*/payment_intents' => Http::response($this->buildPaymentIntentResponse('pi-1')),
            '*/payment_intents/pi-1' => Http::response([
                'status' => 'PENDING',
                'selected_payment_method_id' => 'CREDIT_CARD',
                'next_payment_action' => 'OPEN_STRIPE',
                'next_payment_action_data' => [
                    'stripe_client_secret' => 'cs_test_123',
                ],
                'stripe_public_key' => 'pk_test_123',
            ]),
        ]);

        $client = new PlaytomicClient();
        $requests = [
            new BookingRequestDto('tenant-1', 'res-1', '2026-02-10', '09:00', 90),
        ];

        $result = $client->bookMultipleCourts('token-123', $requests, 'user-1');

        $this->assertFalse($result->allSucceeded);
        $this->assertCount(0, $result->successful);
        $this->assertCount(1, $result->failed);
    }

    public function test_single_court_succeeds(): void
    {
        Http::fake([
            '*/payment_intents' => Http::response($this->buildPaymentIntentResponse('pi-1')),
            '*/payment_intents/pi-1' => Http::response($this->buildUpdateResponse()),
            '*/payment_intents/pi-1/confirmation' => Http::response($this->buildConfirmResponse('match-1')),
        ]);

        $client = new PlaytomicClient();
        $requests = [
            new BookingRequestDto('tenant-1', 'res-1', '2026-02-10', '09:00', 90),
        ];

        $result = $client->bookMultipleCourts('token-123', $requests, 'user-1');

        $this->assertTrue($result->allSucceeded);
        $this->assertCount(1, $result->successful);
        $this->assertCount(0, $result->failed);
    }
}
