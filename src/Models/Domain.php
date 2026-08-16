<?php

namespace Zemail\Models;

class Domain
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $allowedTypes
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['allowed_types']
        );
    }
}
