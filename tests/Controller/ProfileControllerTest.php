<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileControllerTest extends CustomWebTestCase
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

    public function testShowingCurrentUserReturnsAuthenticatedUserData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->getRequest('/api/profile');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getId(), $content['id']);
        self::assertSame($user->getFirstname(), $content['firstname']);
        self::assertSame($user->getLastname(), $content['lastname']);
        self::assertSame($user->getEmail(), $content['email']);
        self::assertSame($user->getCompany()->getId(), $content['companyId']);
    }

    public function testShowingCurrentUserAsRegularUserReturnsAuthenticatedUserData(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->getRequest('/api/profile');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getId(), $content['id']);
    }

    public function testShowingCurrentUserWithoutTokenReturnsUnauthorized(): void
    {
        $this->getRequest('/api/profile');

        self::assertResponseIsUnauthorized();
    }

    public function testUpdatingOwnProfileReturnsUpdatedData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->putRequest('/api/profile', [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated', $content['firstname']);
        self::assertSame('Name', $content['lastname']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testUpdatingOwnProfileAsRegularUserReturnsUpdatedData(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->putRequest('/api/profile', [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
        ]);

        self::assertResponseIsOk();
    }

    public function testUpdatingProfileWithoutTokenReturnsUnauthorized(): void
    {
        $this->putRequest('/api/profile', [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
        ]);

        self::assertResponseIsUnauthorized();
    }
}
