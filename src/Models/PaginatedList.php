<?php

namespace Zemail\Models;

class PaginatedList
{
    public function __construct(
        public readonly array $data,
        public readonly bool $hasMore,
        public readonly string|int|null $nextCursor,
        public readonly array $meta
    ) {}
}
