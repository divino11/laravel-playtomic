<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class StripePaymentRequiredDto
{
    public function __construct(
        public string $paymentIntentId,
        public string $stripePublicKey,
        public string $stripeClientSecret,
        public string $price,
    ) {
    }

    /**
     * @return array{payment_intent_id: string, stripe_public_key: string, stripe_client_secret: string, price: string}
     */
    public function toArray(): array
    {
        return [
            'payment_intent_id' => $this->paymentIntentId,
            'stripe_public_key' => $this->stripePublicKey,
            'stripe_client_secret' => $this->stripeClientSecret,
            'price' => $this->price,
        ];
    }
}
