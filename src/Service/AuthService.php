<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function hashPassword(User $user, string $password): string
    {
        return $this->passwordHasher->hashPassword(
            $user,
            $password
        );
    }

    public function isPasswordValid(User $user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}