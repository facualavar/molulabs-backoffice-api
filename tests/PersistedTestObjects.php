<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Enum\ExportType;
use Doctrine\ORM\EntityManagerInterface;

final class PersistedTestObjects
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    private function persistAndFlushEntities(array $entities = []): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    public function getAdminUser(?Company $company = null, ?string $documentNumber = null): User
    {
        $company ??= $this->getCompany();
        $user = TestObjects::getAdminUser($company, $documentNumber);

        $this->persistAndFlushEntities([$user]);
        return $user;
    }

    public function getSuperAdminUser(?Company $company = null, ?string $documentNumber = null): User
    {
        $company ??= $this->getCompany();
        $user = TestObjects::getSuperAdminUser($company, $documentNumber);

        $this->persistAndFlushEntities([$user]);
        return $user;
    }

    public function getUser(string $firstname = 'Regular', ?Company $company = null, ?string $documentNumber = null): User
    {
        $company ??= $this->getCompany();
        $user = TestObjects::getUser($company, $firstname, $documentNumber);

        $this->persistAndFlushEntities([$user]);
        return $user;
    }

    public function getCompany(string $name = 'Acme', ?string $documentNumber = null): Company
    {
        $company = TestObjects::getCompany($name, $documentNumber);

        $this->persistAndFlushEntities([$company]);
        return $company;
    }

    public function getExportJob(User $requestedBy, ExportType $type = ExportType::USERS): ExportJob
    {
        $exportJob = TestObjects::getExportJob($requestedBy, $type);

        $this->persistAndFlushEntities([$exportJob]);
        return $exportJob;
    }
}
