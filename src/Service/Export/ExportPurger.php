<?php

namespace App\Service\Export;

use App\Repository\ExportJobRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ExportPurger
{
    public function __construct(
        private readonly ExportJobRepository $exportJobRepository,
        private readonly ExportStorage $storage,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Deletes the files of the exports completed before $before.
     *
     * @return int how many files were deleted
     */
    public function purgeCompletedBefore(\DateTimeImmutable $before): int
    {
        $jobs = $this->exportJobRepository->findWithFileCompletedBefore($before);

        foreach ($jobs as $job) {
            $this->storage->delete($job->getFileName());
            $job->discardFile();
        }

        $this->entityManager->flush();

        return count($jobs);
    }
}
