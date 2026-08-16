<?php

namespace Zemail\Models;

class Usage
{
    public function __construct(
        public readonly array $mailboxes,
        public readonly array $storage,
        public readonly array $developerApi
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['mailboxes'],
            $data['storage'],
            $data['developer_api']
        );
    }
}
