<?php

namespace App\Dto\Company;

use App\Dto\Export\ExportFilters;
use App\Enum\ExportType;

final class CompanyExportFilters implements ExportFilters
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $documentNumber = null,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public static function fromArray(array $filters): self
    {
        return new self($filters['name'] ?? null, $filters['documentNumber'] ?? null);
    }

    public function type(): ExportType
    {
        return ExportType::COMPANIES;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'documentNumber' => $this->documentNumber,
        ];
    }
}
