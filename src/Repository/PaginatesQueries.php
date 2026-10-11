<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use Doctrine\ORM\QueryBuilder;

trait PaginatesQueries
{
    private function paginate(QueryBuilder $queryBuilder, string $alias, int $page, int $limit, string $direction = 'ASC'): PaginatedResult
    {
        $total = (int) (clone $queryBuilder)
            ->select("COUNT($alias.id)")
            ->getQuery()
            ->getSingleScalarResult();

        $items = $queryBuilder
            ->orderBy("$alias.id", $direction)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult($items, $total, $page, $limit);
    }
}
