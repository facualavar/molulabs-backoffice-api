<?php

namespace App\Dto\User;

use App\Entity\User;

final class CurrentUserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getFirstname(),
            $user->getLastname(),
            $user->getEmail(),
        );
    }
}
