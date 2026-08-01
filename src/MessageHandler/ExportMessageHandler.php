<?php

namespace App\MessageHandler;

use App\Message\ExportMessage;
use App\Repository\ExportJobRepository;
use App\Service\Export\XlsxExportWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ExportMessageHandler
{
    public function __construct(
        private readonly ExportJobRepository $exportJobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly XlsxExportWriter $writer,
    ) {}

    public function __invoke(ExportMessage $message): void
    {
        $job = $this->exportJobRepository->find($message->exportJobId);

        if (!$job) {
            return;
        }

        $job->markAsProcessing();
        $this->entityManager->flush();

        try {
            $filePath = $this->writer->write($job);

            $job->markAsReady($filePath);
        } catch (\Throwable $exception) {
            $job->markAsFailed($exception->getMessage());
        }

        $this->entityManager->flush();
    }
}
