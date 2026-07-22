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

    public function create(Company $company, string $firstname, string $lastname, string $email, string $password): User
    {
        $user = new User($company, $firstname, $lastname, $email, $password);
        $user->setPassword($this->authService->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, string $firstname, string $lastname, string $email): User
    {
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);

        $this->entityManager->flush();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
