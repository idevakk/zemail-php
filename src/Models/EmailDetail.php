<?php

namespace Zemail\Models;

class EmailDetail
{
    /**
     * @param  EmailAttachment[]  $attachments
     */
    public function __construct(
        public readonly int $id,
        public readonly string $sender,
        public readonly ?string $senderName,
        public readonly string $senderEmail,
        public readonly string $subject,
        public readonly string $preview,
        public readonly ?string $receivedAt,
        public readonly bool $isRead,
        public readonly bool $isBlocked,
        public readonly ?string $blockedDomain,
        public readonly ?string $bodyText,
        public readonly ?string $bodyHtml,
        public readonly array $attachments
    ) {}

    public static function fromArray(array $data): self
    {
        $attachments = array_map(function ($attachmentData) {
            return EmailAttachment::fromArray($attachmentData);
        }, $data['attachments'] ?? []);

        return new self(
            $data['id'],
            $data['sender'],
            $data['sender_name'] ?? null,
            $data['sender_email'],
            $data['subject'],
            $data['preview'],
            $data['received_at'] ?? null,
            $data['is_read'],
            $data['is_blocked'],
            $data['blocked_domain'] ?? null,
            $data['body_text'] ?? null,
            $data['body_html'] ?? null,
            $attachments
        );
    }
}
