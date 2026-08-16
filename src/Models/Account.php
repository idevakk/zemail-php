<?php

namespace Zemail\Models;

class Account
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $emailVerifiedAt,
        public readonly string $tier,
        public readonly string $tierLabel,
        public readonly ?array $currentPlan,
        public readonly array $developerApi
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['email'],
            $data['email_verified_at'] ?? null,
            $data['tier'],
            $data['tier_label'],
            $data['current_plan'] ?? null,
            $data['developer_api']
        );
    }
}
