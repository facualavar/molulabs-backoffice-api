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

    public function findByFilters(User $viewer, ?string $name, ?UserRole $role, ?string $documentNumber, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->buildFilteredQuery($viewer, $name, $role, $documentNumber);

        return $this->paginate($queryBuilder, 'u', $page, $limit);
    }

    /**
     * @return iterable<User>
     */
    public function iterateByFilters(User $viewer, ?string $name, ?UserRole $role, ?string $documentNumber = null): iterable
    {
        return $this->buildFilteredQuery($viewer, $name, $role, $documentNumber)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    public function existsByCompany(Company $company): bool
    {
        return $this->count(['company' => $company]) > 0;
    }

    public function findOneByDocumentNumber(string $documentNumber): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.document.number = :documentNumber')
            ->setParameter('documentNumber', $documentNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function buildFilteredQuery(User $viewer, ?string $name, ?UserRole $role, ?string $documentNumber): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->addSelect('c');

        $this->applyVisibility($queryBuilder, $viewer);

        if ($name) {
            $queryBuilder
                ->andWhere('u.firstname LIKE :name OR u.lastname LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($role) {
            $queryBuilder
                ->andWhere('u.role = :role')
                ->setParameter('role', $role);
        }

        if ($documentNumber) {
            $queryBuilder
                ->andWhere('u.document.number LIKE :documentNumber')
                ->setParameter('documentNumber', '%' . $documentNumber . '%');
        }

        return $queryBuilder;
    }

    /**
     * The query form of User::canManage(): keep both in step.
     */
    private function applyVisibility(QueryBuilder $queryBuilder, User $viewer): void
    {
        if ($viewer->isSuperAdmin()) {
            return;
        }

        $queryBuilder
            ->andWhere('u.company = :viewerCompany')
            ->andWhere('u.role != :superAdminRole')
            ->setParameter('viewerCompany', $viewer->getCompany())
            ->setParameter('superAdminRole', UserRole::SUPERADMIN);
    }
}
