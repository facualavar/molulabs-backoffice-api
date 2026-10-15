<?php

namespace App\Dto\User;

use App\Enum\UserRole;

final class UserFilterRequest
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?UserRole $role = null,
        public readonly ?string $documentNumber = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}

    public function toExportFilters(): UserExportFilters
    {
        return new UserExportFilters($this->name, $this->role, $this->documentNumber);
    }
}
