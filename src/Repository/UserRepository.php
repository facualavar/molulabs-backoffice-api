<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByFilters(?string $name, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('u');

        if ($name) {
            $queryBuilder
                ->andWhere('u.firstname LIKE :name OR u.lastname LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        $total = (int) (clone $queryBuilder)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $queryBuilder
            ->orderBy('u.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return new PaginatedResult($items, $total);
    }
}
