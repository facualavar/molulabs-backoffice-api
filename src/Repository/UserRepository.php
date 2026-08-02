<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    use PaginatesQueries;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByFilters(?Company $company, ?string $name, ?UserRole $role, ?string $documentNumber, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->buildFilteredQuery($company, $name, $role, $documentNumber);

        return $this->paginate($queryBuilder, 'u', $page, $limit);
    }

    /**
     * @return iterable<User>
     */
    public function iterateByFilters(?Company $company, ?string $name, ?UserRole $role, ?string $documentNumber = null): iterable
    {
        return $this->buildFilteredQuery($company, $name, $role, $documentNumber)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    private function buildFilteredQuery(?Company $company, ?string $name, ?UserRole $role, ?string $documentNumber): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->addSelect('c');

        if ($company) {
            $queryBuilder
                ->andWhere('u.company = :company')
                ->setParameter('company', $company);
        }

        if ($name) {
            $queryBuilder
                ->andWhere('u.firstname LIKE :name OR u.lastname LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($role) {
            $queryBuilder
                ->andWhere('u.roles = :role')
                ->setParameter('role', [$role->value], 'jsonb');
        }

        if ($documentNumber) {
            $queryBuilder
                ->andWhere('c.documentNumber LIKE :documentNumber')
                ->setParameter('documentNumber', '%' . $documentNumber . '%');
        }

        return $queryBuilder;
    }
}
