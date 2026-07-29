<?php

namespace App\Tests\Controller\Bundles;

use App\Tests\Controller\CustomWebTestCase;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class LoginTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($entityManager);
    }

    protected function tearDown(): void
    {
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
}
