<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findByFilters(?Company $company, ?string $name, ?UserRole $role, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('u');

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

        return $this->paginate($queryBuilder, 'u', $page, $limit);
    }
}
