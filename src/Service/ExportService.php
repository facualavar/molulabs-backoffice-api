<?php

namespace App\Service;

use App\Dto\Export\ExportFilters;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Message\ExportMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {}

    public function create(ExportFilters $filters, User $requestedBy): ExportJob
    {
        $job = new ExportJob($filters->type(), $filters->toArray(), $requestedBy);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new ExportMessage($job->getId()));

        return $job;
    }
}
