<?php

namespace App\Dto\Pagination;

final class PaginatedResult
{
    /**
     * @param list<mixed> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {}
}
