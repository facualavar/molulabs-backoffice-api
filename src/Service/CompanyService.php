<?php

namespace App\Service;

use App\Entity\Company;
use App\Enum\DocumentType;
use App\Exception\CompanyHasUsersException;
use App\Exception\DuplicateDocumentNumberException;
use App\Exception\DuplicateEmailException;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CompanyService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
        private UniqueValueGuard $uniqueValues,
    ) {}

    public function create(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): Company
    {
        $company = new Company($name, $email, $documentType, $documentNumber, $phone);

        $this->assertIsUnique($company);

        $this->entityManager->persist($company);
        $this->flushRejectingDuplicates($company);

        return $company;
    }

    public function update(Company $company, string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): Company
    {
        $company->setName($name);
        $company->setEmail($email);
        $company->setDocument($documentType, $documentNumber);
        $company->setPhone($phone);

        $this->assertIsUnique($company);

        $this->flushRejectingDuplicates($company);

        return $company;
    }

    public function delete(Company $company): void
    {
        if ($this->userRepository->existsByCompany($company)) {
            throw new CompanyHasUsersException();
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();
    }

    private function assertIsUnique(Company $company): void
    {
        $this->uniqueValues->assertFree(
            $this->companyRepository->findOneByDocumentNumber($company->getDocumentNumber()),
            $company,
            fn () => new DuplicateDocumentNumberException($company->getDocumentNumber()),
        );
        $this->uniqueValues->assertFree(
            $this->companyRepository->findOneByEmail($company->getEmail()),
            $company,
            fn () => new DuplicateEmailException($company->getEmail()),
        );
    }

    private function flushRejectingDuplicates(Company $company): void
    {
        $this->uniqueValues->flush([
            Company::EMAIL_UNIQUE => fn () => new DuplicateEmailException($company->getEmail()),
            Company::DOCUMENT_NUMBER_UNIQUE => fn () => new DuplicateDocumentNumberException($company->getDocumentNumber()),
        ]);
    }
}
