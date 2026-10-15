<?php

namespace App\Service\Export;

use App\Repository\ExportJobRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A worker that dies while writing an export (out of memory, restart, kill) never records the
 * outcome, and the job would stay in processing forever.
 */
final class StaleExportFailer
{
    public const string INTERRUPTED_MESSAGE = 'La exportación se interrumpió.';

    public function __construct(
        private readonly ExportJobRepository $exportJobRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Marks as failed the exports that started processing before $before.
     *
     * @return int how many exports were marked
     */
    public function failStartedBefore(\DateTimeImmutable $before): int
    {
        $jobs = $this->exportJobRepository->findProcessingStartedBefore($before);

        foreach ($jobs as $job) {
            $job->markAsFailed(self::INTERRUPTED_MESSAGE);
        }

        $this->entityManager->flush();

        return count($jobs);
    }
}
