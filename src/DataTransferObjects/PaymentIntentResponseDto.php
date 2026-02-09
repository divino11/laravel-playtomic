<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class PaymentIntentResponseDto
{
    /**
     * @param  array<int, string>  $availablePaymentMethods
     */
    public function __construct(
        public string $paymentIntentId,
        public array $availablePaymentMethods = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $methods = [];

        foreach ($data['available_payment_methods'] ?? [] as $entry) {
            if (is_string($entry)) {
                $methods[] = $entry;
            } elseif (is_array($entry)) {
                $code = $entry['type']
                    ?? $entry['code']
                    ?? $entry['payment_method_id']
                    ?? $entry['method_type']
                    ?? null;

                if (is_string($code)) {
                    $methods[] = $code;
                }
            }
        }

        return new self(
            paymentIntentId: $data['payment_intent_id'] ?? '',
            availablePaymentMethods: array_values(array_unique($methods)),
        );
    }
}
