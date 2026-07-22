<?php

namespace App\Dto\Company;

use App\Entity\Company;

final class CompanyResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function fromEntity(Company $company): self
    {
        return new self(
            $company->getId(),
            $company->getName(),
            $company->getEmail(),
            $company->getCreatedAt(),
        );
    }
}
