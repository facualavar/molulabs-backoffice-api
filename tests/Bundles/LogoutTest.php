<?php

namespace App\Tests\Controller\Bundles;

use App\Entity\RefreshToken;
use App\Tests\Controller\CustomWebTestCase;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class LogoutTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($this->entityManager);
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testLogoutInvalidatesRefreshToken(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();

        $refreshToken = RefreshToken::createForUserWithTtl(bin2hex(random_bytes(64)), $user, 3600);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        $this->postRequest('/api/logout', ['refresh_token' => $refreshToken->getRefreshToken()]);

        self::assertResponseIsOk();

        $tokenString = $refreshToken->getRefreshToken();
        $this->entityManager->clear();
        $persistedToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy(['refreshToken' => $tokenString]);
        self::assertNull($persistedToken);
    }

    public function testLogoutWithoutRefreshTokenReturnsBadRequest(): void
    {
        $this->postRequest('/api/logout', []);

        self::assertResponseStatusCodeSame(400);
    }

    public function testLogoutWithAlreadyInvalidRefreshTokenReturnsOk(): void
    {
        $this->postRequest('/api/logout', ['refresh_token' => 'already-invalid-token']);

        self::assertResponseIsOk();
    }
}
