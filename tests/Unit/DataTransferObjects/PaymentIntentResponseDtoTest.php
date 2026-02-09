<?php

declare(strict_types=1);

namespace Divino11\Playtomic\Tests\Unit\DataTransferObjects;

use Divino11\Playtomic\DataTransferObjects\PaymentIntentResponseDto;
use PHPUnit\Framework\TestCase;

class PaymentIntentResponseDtoTest extends TestCase
{
    public function test_from_api_response_with_string_methods(): void
    {
        $data = [
            'payment_intent_id' => 'pi-123',
            'available_payment_methods' => ['CASH', 'CREDIT_CARD'],
        ];

        $dto = PaymentIntentResponseDto::fromApiResponse($data);

        $this->assertEquals('pi-123', $dto->paymentIntentId);
        $this->assertEquals(['CASH', 'CREDIT_CARD'], $dto->availablePaymentMethods);
    }

    public function test_from_api_response_with_object_methods(): void
    {
        $data = [
            'payment_intent_id' => 'pi-456',
            'available_payment_methods' => [
                ['type' => 'CASH'],
                ['code' => 'CREDIT_CARD'],
                ['payment_method_id' => 'IDEAL'],
            ],
        ];

        $dto = PaymentIntentResponseDto::fromApiResponse($data);

        $this->assertEquals('pi-456', $dto->paymentIntentId);
        $this->assertEquals(['CASH', 'CREDIT_CARD', 'IDEAL'], $dto->availablePaymentMethods);
    }

    public function test_from_api_response_deduplicates_methods(): void
    {
        $data = [
            'payment_intent_id' => 'pi-789',
            'available_payment_methods' => ['CASH', 'CASH', 'CREDIT_CARD'],
        ];

        $dto = PaymentIntentResponseDto::fromApiResponse($data);

        $this->assertEquals(['CASH', 'CREDIT_CARD'], $dto->availablePaymentMethods);
    }

    public function test_from_api_response_with_empty_methods(): void
    {
        $data = [
            'payment_intent_id' => 'pi-empty',
        ];

        $dto = PaymentIntentResponseDto::fromApiResponse($data);

        $this->assertEquals('pi-empty', $dto->paymentIntentId);
        $this->assertEquals([], $dto->availablePaymentMethods);
    }
}
