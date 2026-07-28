<?php

namespace App\Dto\Pagination;

final class PaginationMeta
{
    public readonly int $totalPages;

    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total,
    ) {
        $this->totalPages = $limit > 0 ? (int) ceil($total / $limit) : 0;
    }
}
