<?php

namespace App\Service;

use App\Entity\Company;
use App\Enum\DocumentType;
use Doctrine\ORM\EntityManagerInterface;

final class CompanyService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function create(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): Company
    {
        $company = new Company($name, $email, $documentType, $documentNumber, $phone);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    public function update(Company $company, string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): Company
    {
        $company->setName($name);
        $company->setEmail($email);
        $company->setDocument($documentType, $documentNumber);
        $company->setPhone($phone);

        $this->entityManager->flush();

        return $company;
    }

    public function delete(Company $company): void
    {
        $this->entityManager->remove($company);
        $this->entityManager->flush();
    }
}
