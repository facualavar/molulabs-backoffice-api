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

    /**
     * @param array{id: int, name: string} $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            $row['id'],
            $row['name'],
        );
    }
}
