<?php

namespace App\Dto\User;

use App\Entity\User;

final class UserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly int $companyId,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getFirstname(),
            $user->getLastname(),
            $user->getEmail(),
            $user->getCompany()->getId(),
        );
    }
}
