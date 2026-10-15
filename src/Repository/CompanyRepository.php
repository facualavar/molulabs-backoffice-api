<?php

namespace App\Repository;

use App\Dto\Pagination\PaginatedResult;
use App\Entity\Company;
use App\Enum\DocumentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
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
        return $this->paginate($this->buildFilteredQuery($name, $documentNumber), 'c', $page, $limit);
    }

    /**
     * Plain rows instead of entities: an export reads every match, and hydrating them would keep
     * each one in memory until the end.
     *
     * @return iterable<array{id: int, name: string, email: string, documentType: DocumentType, documentNumber: string, phone: string, createdAt: \DateTimeImmutable}>
     */
    public function iterateExportRowsByFilters(?string $name, ?string $documentNumber): iterable
    {
        return $this->buildFilteredQuery($name, $documentNumber)
            ->select('c.id', 'c.name', 'c.email', 'c.document.type AS documentType', 'c.document.number AS documentNumber', 'c.phone', 'c.createdAt')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->toIterable([], AbstractQuery::HYDRATE_ARRAY);
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

    private function buildFilteredQuery(?string $name, ?string $documentNumber): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($name) {
            $this->applyNameFilter($queryBuilder, $name);
        }

        if ($documentNumber) {
            $this->applyDocumentNumberFilter($queryBuilder, $documentNumber);
        }

        return $queryBuilder;
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
