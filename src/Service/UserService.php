<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthService $authService,
    ) {}

    public function create(Company $company, string $firstname, string $lastname, string $email, string $password, string $role): User
    {
        $user = new User($company, $firstname, $lastname, $email, $password, $role);
        $user->setPassword($this->authService->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, string $firstname, string $lastname, string $email, string $role): User
    {
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setRoles([$role]);

        $this->entityManager->flush();

        return $user;
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->setPassword($this->authService->hashPassword($user, $password));

        $this->entityManager->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
