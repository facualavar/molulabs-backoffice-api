<?php

namespace App\Dto\Company;

use App\Entity\Company;

final class CompanySummaryResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}

    public static function fromEntity(Company $company): self
    {
        return new self(
            $company->getId(),
            $company->getName(),
        );
    }
}
