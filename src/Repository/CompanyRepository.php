<?php

namespace App\Repository;

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

    /**
     * @return list<Company>
     */
    public function findByFilters(?string $name, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($name) {
            $queryBuilder
                ->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        return $queryBuilder
            ->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
