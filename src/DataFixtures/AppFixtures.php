<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
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
        $company = new Company('Acme', 'acme@company.com');

        $user = new User($company, 'Admin', 'Company', 'admin@company.com', '');
        $user->setPassword($this->authService->hashPassword($user, 'password'));

        $manager->persist($company);
        $manager->persist($user);
        $manager->flush();
    }
}
