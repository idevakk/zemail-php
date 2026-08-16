<?php

namespace Zemail\Models;

class Subscription
{
    public function __construct(
        public readonly string $status,
        public readonly string $tier,
        public readonly ?array $plan,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly ?string $cancelledAt
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['status'],
            $data['tier'],
            $data['plan'] ?? null,
            $data['starts_at'] ?? null,
            $data['ends_at'] ?? null,
            $data['cancelled_at'] ?? null
        );
    }
}
