<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class CurrentUserControllerTest extends CustomWebTestCase
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

    public function testShowingCurrentUserReturnsMinimalAuthenticatedUserData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->getRequest('/api/me');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getId(), $content['id']);
        self::assertSame($user->getFirstname(), $content['firstname']);
        self::assertSame($user->getLastname(), $content['lastname']);
        self::assertSame($user->getEmail(), $content['email']);
        self::assertArrayNotHasKey('companyId', $content);
    }

    public function testShowingCurrentUserAsRegularUserReturnsMinimalAuthenticatedUserData(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->getRequest('/api/me');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getId(), $content['id']);
    }

    public function testShowingCurrentUserWithoutTokenReturnsUnauthorized(): void
    {
        $this->getRequest('/api/me');

        self::assertResponseIsUnauthorized();
    }
}
