<?php

namespace App\Dto\Pagination;

final class PaginatedResponse
{
    /**
     * @param list<mixed> $data
     */
    public function __construct(
        public readonly array $data,
        public readonly PaginationMeta $meta,
    ) {}

    /**
     * @param callable(mixed): mixed $mapper
     */
    public static function fromResult(PaginatedResult $result, callable $mapper): self
    {
        return new self(
            array_map($mapper, $result->items),
            new PaginationMeta($result->page, $result->limit, $result->total),
        );
    }
}
