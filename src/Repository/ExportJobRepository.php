<?php

namespace App\Repository;

use App\Entity\ExportJob;
use App\Entity\User;
use App\Enum\ExportStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExportJob>
 */
class ExportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportJob::class);
    }

    public function existsByRequestedBy(User $user): bool
    {
        return $this->count(['requestedBy' => $user]) > 0;
    }

    /**
     * Ready exports whose file is still stored and was written before $before.
     *
     * @return list<ExportJob>
     */
    public function findWithFileCompletedBefore(\DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->andWhere('j.fileName IS NOT NULL')
            ->andWhere('j.completedAt < :before')
            ->setParameter('status', ExportStatus::READY)
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }
}
