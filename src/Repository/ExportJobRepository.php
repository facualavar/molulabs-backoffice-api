<?php

namespace App\Repository;

use App\Entity\ExportJob;
use App\Entity\User;
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
}
