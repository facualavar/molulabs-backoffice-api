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
    use FlushesUniqueFields;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
    ) {}

    public function create(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): Company
    {
        $company = new Company($name, $email, $documentType, $documentNumber, $phone);

        $this->assertDocumentNumberIsUnique($company);
        $this->assertEmailIsUnique($company);

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

        $this->assertDocumentNumberIsUnique($company);
        $this->assertEmailIsUnique($company);

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

    private function assertDocumentNumberIsUnique(Company $company): void
    {
        $existing = $this->companyRepository->findOneByDocumentNumber($company->getDocumentNumber());

        if ($existing && $existing->getId() !== $company->getId()) {
            throw new DuplicateDocumentNumberException($company->getDocumentNumber());
        }
    }

    private function assertEmailIsUnique(Company $company): void
    {
        $existing = $this->companyRepository->findOneByEmail($company->getEmail());

        if ($existing && $existing->getId() !== $company->getId()) {
            throw new DuplicateEmailException($company->getEmail());
        }
    }

    private function flushRejectingDuplicates(Company $company): void
    {
        $this->flushUnique($this->entityManager, [
            Company::EMAIL_UNIQUE => fn () => new DuplicateEmailException($company->getEmail()),
            Company::DOCUMENT_NUMBER_UNIQUE => fn () => new DuplicateDocumentNumberException($company->getDocumentNumber()),
        ]);
    }
}
