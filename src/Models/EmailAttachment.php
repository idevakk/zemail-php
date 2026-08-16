<?php

namespace Zemail\Models;

class EmailAttachment
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?int $size,
        public readonly bool $downloadable
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['name'] ?? null,
            $data['size'] ?? null,
            $data['downloadable']
        );
    }
}
