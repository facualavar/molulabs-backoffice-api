<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Enum\UserRole;
use App\Service\AuthService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $acme = new Company('Acme', 'acme@company.com', DocumentType::CUIT, '30-12345678-1', '011-4444-5555');
        $globex = new Company('Globex', 'globex@company.com', DocumentType::CUIT, '30-98765432-1', '011-3333-2222');

        $superAdmin = $this->createUser($acme, 'SuperAdmin', 'Company', 'superadmin@company.com', '11111111', UserRole::SUPERADMIN);
        $admin = $this->createUser($acme, 'Admin', 'Company', 'admin@company.com', '22222222', UserRole::ADMIN);
        $user = $this->createUser($acme, 'Regular', 'User', 'user@company.com', '33333333', UserRole::USER);

        $manager->persist($acme);
        $manager->persist($globex);
        $manager->persist($superAdmin);
        $manager->persist($admin);
        $manager->persist($user);
        $manager->flush();
    }

    private function createUser(Company $company, string $firstname, string $lastname, string $email, string $documentNumber, UserRole $role): User
    {
        $user = new User($company, $firstname, $lastname, $email, $documentNumber, '', $role);
        $user->setPassword($this->authService->hashPassword($user, 'password'));

        return $user;
    }
}
