<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRole;
use App\Exception\CannotAssignSuperAdminRoleException;
use App\Exception\CannotDeleteOwnUserException;
use App\Exception\DuplicateDocumentNumberException;
use App\Exception\DuplicateEmailException;
use App\Exception\UserHasExportJobsException;
use App\Repository\ExportJobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthService $authService,
        private UserRepository $userRepository,
        private ExportJobRepository $exportJobRepository,
        private UniqueValueGuard $uniqueValues,
    ) {}

    public function create(Company $company, string $firstname, string $lastname, string $email, string $documentNumber, string $password, UserRole $role): User
    {
        $this->assertAssignableRole($role);

        $user = new User($company, $firstname, $lastname, $email, $documentNumber, $password, $role);
        $user->setPassword($this->authService->hashPassword($user, $password));

        $this->assertIsUnique($user);

        $this->entityManager->persist($user);
        $this->flushRejectingDuplicates($user);

        return $user;
    }

    public function update(User $user, string $firstname, string $lastname, string $email, string $documentNumber, UserRole $role): User
    {
        if ($role !== $user->getRole()) {
            $this->assertAssignableRole($role);
        }

        $user->updateProfile($firstname, $lastname, $email, $documentNumber, $role);

        $this->assertIsUnique($user);

        $this->flushRejectingDuplicates($user);

        return $user;
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->setPassword($this->authService->hashPassword($user, $password));

        $this->entityManager->flush();

        return $user;
    }

    public function delete(User $user, User $currentUser): void
    {
        if ($user->getId() === $currentUser->getId()) {
            throw new CannotDeleteOwnUserException();
        }

        if ($this->exportJobRepository->existsByRequestedBy($user)) {
            throw new UserHasExportJobsException();
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function assertAssignableRole(UserRole $role): void
    {
        if ($role === UserRole::SUPERADMIN) {
            throw new CannotAssignSuperAdminRoleException();
        }
    }

    private function assertIsUnique(User $user): void
    {
        $this->uniqueValues->assertFree(
            $this->userRepository->findOneByDocumentNumber($user->getDocumentNumber()),
            $user,
            fn () => new DuplicateDocumentNumberException($user->getDocumentNumber()),
        );
        $this->uniqueValues->assertFree(
            $this->userRepository->findOneByEmail($user->getEmail()),
            $user,
            fn () => new DuplicateEmailException($user->getEmail()),
        );
    }

    private function flushRejectingDuplicates(User $user): void
    {
        $this->uniqueValues->flush([
            User::EMAIL_UNIQUE => fn () => new DuplicateEmailException($user->getEmail()),
            User::DOCUMENT_NUMBER_UNIQUE => fn () => new DuplicateDocumentNumberException($user->getDocumentNumber()),
        ]);
    }
}
