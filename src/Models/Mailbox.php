<?php

namespace Zemail\Models;

class Mailbox
{
    public function __construct(
        public readonly int $id,
        public readonly string $address,
        public readonly string $type,
        public readonly string $domain,
        public readonly ?string $expiresAt,
        public readonly ?string $createdAt,
        public readonly int $unreadCount,
        public readonly int $emailsCount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['address'],
            $data['type'],
            $data['domain'],
            $data['expires_at'] ?? null,
            $data['created_at'] ?? null,
            $data['unread_count'],
            $data['emails_count']
        );
    }
}
