<?php

namespace App\Dto\Company;

final class CompanyFilterRequest
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $documentNumber = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}

    public function toExportFilters(): CompanyExportFilters
    {
        return new CompanyExportFilters($this->name, $this->documentNumber);
    }
}
