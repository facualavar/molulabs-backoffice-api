<?php

namespace App\Tests\Controller\Bundles;

use App\Entity\User;
use App\Tests\Controller\CustomWebTestCase;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;
    private ?UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($entityManager);
    }

    protected function tearDown(): void
    {
        $this->passwordHasher = null;
        $this->persistedTestObjects = null;
        parent::tearDown();
    }

    public function testLoginFailsWhenUserDoesNotExist(): void
    {
        $this->postRequest('/api/login', ['email' => 'admin@company.com', 'password' => 'password']);

        self::assertResponseUnauthorizedWithMessage('Invalid credentials.');
    }

    public function testLoginReturnsTokenWithValidCredentials(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->encryptPassword($user);

        $this->postRequest('/api/login', ['email' => 'admin@company.com', 'password' => 'password']);

        self::assertResponseIsOk();
    }

    private function encryptPassword(User $user): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );

        $user->setPassword($hashedPassword);
    }
}
