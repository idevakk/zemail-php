<?php

namespace Zemail\Models;

class EmailSummary
{
    public function __construct(
        public readonly int $id,
        public readonly string $sender,
        public readonly string $senderEmail,
        public readonly string $subject,
        public readonly string $preview,
        public readonly ?string $receivedAt,
        public readonly bool $isRead,
        public readonly bool $isBlocked,
        public readonly int $attachmentsCount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['sender'],
            $data['sender_email'],
            $data['subject'],
            $data['preview'],
            $data['received_at'] ?? null,
            $data['is_read'],
            $data['is_blocked'],
            $data['attachments_count']
        );
    }
}
