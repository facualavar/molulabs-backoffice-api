<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findByFilters(?string $name, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($name) {
            $queryBuilder
                ->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        $total = (int) (clone $queryBuilder)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $queryBuilder
            ->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult($items, $total);
    }
}
