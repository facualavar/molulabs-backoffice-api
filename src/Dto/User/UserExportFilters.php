<?php

namespace App\Dto\User;

use App\Dto\Export\ExportFilters;
use App\Enum\ExportType;
use App\Enum\UserRole;

final class UserExportFilters implements ExportFilters
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?UserRole $role = null,
        public readonly ?string $documentNumber = null,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public static function fromArray(array $filters): self
    {
        return new self(
            $filters['name'] ?? null,
            isset($filters['role']) ? UserRole::from($filters['role']) : null,
            $filters['documentNumber'] ?? null,
        );
    }

    public function type(): ExportType
    {
        return ExportType::USERS;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role?->value,
            'documentNumber' => $this->documentNumber,
        ];
    }
}
