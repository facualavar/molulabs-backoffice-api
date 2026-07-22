<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;
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

    public function getAdminUser(): User
    {
        $user = TestObjects::getAdminUser();

        $this->persistAndFlushEntities([$user]);
        return $user;
    }

    public function getCompany(): Company
    {
        $company = TestObjects::getCompany();

        $this->persistAndFlushEntities([$company]);
        return $company;
    }
}