<?php

namespace App\Service;

use App\Entity\ExportJob;
use App\Entity\User;
use App\Enum\ExportType;
use App\Message\ExportMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function create(ExportType $type, array $filters, User $requestedBy): ExportJob
    {
        $job = new ExportJob($type, $filters, $requestedBy);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new ExportMessage($job->getId()));

        return $job;
    }
}
