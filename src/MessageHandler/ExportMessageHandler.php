<?php

namespace App\MessageHandler;

use App\Message\ExportMessage;
use App\Repository\ExportJobRepository;
use App\Service\Export\XlsxExportWriter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ExportMessageHandler
{
    private const string FAILURE_MESSAGE = 'No se pudo generar la exportación.';

    public function __construct(
        private readonly ExportJobRepository $exportJobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly XlsxExportWriter $writer,
        private readonly LoggerInterface $logger,
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
            $fileName = $this->writer->write($job);

            $job->markAsReady($fileName);
        } catch (\Throwable $exception) {
            $this->logger->error('Export job {id} failed.', ['id' => $job->getId(), 'exception' => $exception]);

            $job->markAsFailed(self::FAILURE_MESSAGE);
        }

        $this->entityManager->flush();
    }
}
