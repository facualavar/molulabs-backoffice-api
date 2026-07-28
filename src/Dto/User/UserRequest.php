<?php

namespace App\Dto\User;

use App\Enum\UserRole;

final class UserRequest
{
    public function __construct(
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly string $password,
        public readonly int $companyId,
        public readonly UserRole $role,
    ) {}
}
