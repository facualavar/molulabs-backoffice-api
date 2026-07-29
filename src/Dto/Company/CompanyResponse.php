<?php

namespace App\Dto\Company;

use App\Entity\Company;
use App\Enum\DocumentType;

final class CompanyResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly DocumentType $documentType,
        public readonly string $documentNumber,
        public readonly string $phone,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function fromEntity(Company $company): self
    {
        return new self(
            $company->getId(),
            $company->getName(),
            $company->getEmail(),
            $company->getDocumentType(),
            $company->getDocumentNumber(),
            $company->getPhone(),
            $company->getCreatedAt(),
        );
    }
}
