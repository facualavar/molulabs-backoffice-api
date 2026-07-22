<?php

namespace App\Tests\Controller\Bundles;

use App\Tests\Controller\CustomWebTestCase;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RefreshToken;

final class RefreshTokenTest extends CustomWebTestCase
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

    public function testRefreshTokenReturnsNewToken(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();

        $refreshToken = RefreshToken::createForUserWithTtl(bin2hex(random_bytes(64)), $user, 3600);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        $this->postRequest('/api/refresh', ['refresh_token' => $refreshToken->getRefreshToken()]);

        self::assertResponseIsOk();
    }

    public function testRefreshTokenFailsWithInvalidToken(): void
    {
        $this->postRequest('/api/refresh', ['refresh_token' => 'invalid-token']);

        self::assertResponseUnauthorizedWithMessage('JWT Refresh Token Not Found');
    }

    public function testRefreshTokenFailsWithExpiredToken(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();

        $refreshToken = RefreshToken::createForUserWithTtl(bin2hex(random_bytes(64)), $user, -1);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        $this->postRequest('/api/refresh', ['refresh_token' => $refreshToken->getRefreshToken()]);

        self::assertResponseIsUnauthorized();
    }
}
