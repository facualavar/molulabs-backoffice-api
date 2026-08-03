<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    use PaginatesQueries;

    private const int AUTOCOMPLETE_LIMIT = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findByFilters(?string $name, ?string $documentNumber, int $page, int $limit): PaginatedResult
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($name) {
            $this->applyNameFilter($queryBuilder, $name);
        }

        if ($documentNumber) {
            $this->applyDocumentNumberFilter($queryBuilder, $documentNumber);
        }

        return $this->paginate($queryBuilder, 'c', $page, $limit);
    }

    /**
     * @return iterable<Company>
     */
    public function iterateByFilters(?string $name): iterable
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($name) {
            $this->applyNameFilter($queryBuilder, $name);
        }

        return $queryBuilder
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    public function findOneByDocumentNumber(string $documentNumber): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.document.number = :documentNumber')
            ->setParameter('documentNumber', $documentNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByEmail(string $email): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function search(string $name): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $this->applyNameFilter($queryBuilder, $name);

        return $queryBuilder
            ->select('c.id', 'c.name')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(self::AUTOCOMPLETE_LIMIT)
            ->getQuery()
            ->getArrayResult();
    }

    private function applyNameFilter(QueryBuilder $queryBuilder, string $name): void
    {
        $queryBuilder
            ->andWhere('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%');
    }

    private function applyDocumentNumberFilter(QueryBuilder $queryBuilder, string $documentNumber): void
    {
        $queryBuilder
            ->andWhere('c.document.number LIKE :documentNumber')
            ->setParameter('documentNumber', '%' . $documentNumber . '%');
    }
}
