<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class UserControllerTest extends CustomWebTestCase
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

    public function testListingUsersReturnsSuccessfulResponse(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->getRequest('/api/users');

        self::assertResponseIsOk();
    }

    public function testShowingExistingUserReturnsItsData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->getRequest('/api/users/' . $user->getId());

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getFirstname(), $content['firstname']);
        self::assertSame($user->getLastname(), $content['lastname']);
        self::assertSame($user->getEmail(), $content['email']);
        self::assertSame($user->getCompany()->getId(), $content['companyId']);
    }

    public function testListingUsersAsRegularUserReturnsForbidden(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->getRequest('/api/users');

        self::assertResponseIsForbidden();
    }

    public function testCreatingUserAsRegularUserReturnsForbidden(): void
    {
        $actingUser = $this->persistedTestObjects->getUser();
        $this->loginAs($actingUser);
        $company = $this->persistedTestObjects->getCompany();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => $company->getId(),
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsForbidden();
    }

    public function testCreatingUserWithValidDataReturnsCreatedUser(): void
    {
        $actingUser = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($actingUser);
        $company = $this->persistedTestObjects->getCompany();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => $company->getId(),
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame('New', $content['firstname']);
        self::assertSame('User', $content['lastname']);
        self::assertSame('new@company.com', $content['email']);
        self::assertSame($company->getId(), $content['companyId']);
    }

    public function testCreatingUserWithNonExistentCompanyReturnsNotFound(): void
    {
        $actingUser = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($actingUser);

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => 0,
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsNotFound();
    }

    public function testUpdatingExistingUserReturnsUpdatedData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->putRequest('/api/users/' . $user->getId(), [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
            'password' => 'password',
            'companyId' => $user->getCompany()->getId(),
            'role' => 'ROLE_ADMIN',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated', $content['firstname']);
        self::assertSame('Name', $content['lastname']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testDeletingExistingUserReturnsNoContent(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->deleteRequest('/api/users/' . $user->getId());

        self::assertResponseIsNoContent();
    }
}
