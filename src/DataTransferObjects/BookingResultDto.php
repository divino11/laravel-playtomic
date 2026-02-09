<?php

declare(strict_types=1);

namespace Divino11\Playtomic\DataTransferObjects;

readonly class BookingResultDto
{
    public function __construct(
        public string $matchId,
        public string $status,
        public string $courtName,
        public string $startDate,
        public string $startTime,
        public int $duration,
        public string $price,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        // The confirmation response wraps match data inside cart.item.cart_item_data
        $cartData = $data['cart']['item']['cart_item_data'] ?? [];

        return new self(
            matchId: $cartData['match_id'] ?? $data['match_id'] ?? $data['payment_intent_id'] ?? $data['id'] ?? '',
            status: $data['status'] ?? 'CONFIRMED',
            courtName: $data['resource']['name'] ?? $data['court_name'] ?? '',
            startDate: isset($cartData['start'])
            ? substr($cartData['start'], 0, 10)
            : ($data['start_date'] ?? ''),
            startTime: isset($cartData['start'])
            ? substr($cartData['start'], 11, 5)
            : ($data['start_time'] ?? ''),
            duration: (int) ($cartData['duration'] ?? $data['duration'] ?? 0),
            price: (string) ($data['price'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'match_id' => $this->matchId,
            'status' => $this->status,
            'court_name' => $this->courtName,
            'start_date' => $this->startDate,
            'start_time' => $this->startTime,
            'duration' => $this->duration,
            'price' => $this->price,
        ];
    }
}
