<?php

namespace App\Dto\User;

use App\Dto\Company\CompanySummaryResponse;
use App\Entity\User;
use App\Enum\UserRole;

final class UserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly CompanySummaryResponse $company,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getFirstname(),
            $user->getLastname(),
            $user->getEmail(),
            $user->getRole(),
            CompanySummaryResponse::fromEntity($user->getCompany()),
        );
    }
}
