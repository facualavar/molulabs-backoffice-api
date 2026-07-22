<?php

namespace App\Service;

use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;

final class CompanyService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function create(string $name, string $email): Company
    {
        $company = new Company($name, $email);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    public function update(Company $company, string $name, string $email): Company
    {
        $company->setName($name);
        $company->setEmail($email);

        $this->entityManager->flush();

        return $company;
    }

    public function delete(Company $company): void
    {
        $this->entityManager->remove($company);
        $this->entityManager->flush();
    }
}
