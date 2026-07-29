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

    public function testShowingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testShowingUserFromAnotherCompanyAsSuperAdminReturnsItsData(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsOk();
    }

    public function testListingUsersFilteredByNameReturnsMatchingUsers(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $matchingUser = $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?name=Wanda');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersRespectsLimit(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersRespectsPage(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $lastUser = $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($lastUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersReturnsPaginationMeta(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame(2, $content['meta']['page']);
        self::assertSame(2, $content['meta']['limit']);
        self::assertSame(3, $content['meta']['total']);
        self::assertSame(2, $content['meta']['totalPages']);
    }

    public function testListingUsersOnlyReturnsUsersFromOwnCompany(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce');

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersAsSuperAdminReturnsUsersFromAllCompanies(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);
        $this->persistedTestObjects->getUser('Wanda');
        $this->persistedTestObjects->getUser('Bruce');

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(3, $content['data']);
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

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame('New', $content['firstname']);
        self::assertSame('User', $content['lastname']);
        self::assertSame('new@company.com', $content['email']);
        self::assertSame($actingUser->getCompany()->getId(), $content['companyId']);
    }

    public function testCreatingUserWithSuperAdminRoleReturnsBadRequest(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => $superAdmin->getCompany()->getId(),
            'role' => 'ROLE_SUPERADMIN',
        ]);

        self::assertResponseIsBadRequest();
    }

    public function testCreatingUserAsRegularAdminIgnoresProvidedCompanyId(): void
    {
        $actingUser = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($actingUser);
        $otherCompany = $this->persistedTestObjects->getCompany();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => $otherCompany->getId(),
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame($actingUser->getCompany()->getId(), $content['companyId']);
    }

    public function testCreatingUserAsSuperAdminForOtherCompanyReturnsCreatedUser(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);
        $otherCompany = $this->persistedTestObjects->getCompany();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'password' => 'password',
            'companyId' => $otherCompany->getId(),
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame($otherCompany->getId(), $content['companyId']);
    }

    public function testCreatingUserAsSuperAdminWithNonExistentCompanyReturnsNotFound(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);

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
            'role' => 'ROLE_ADMIN',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated', $content['firstname']);
        self::assertSame('Name', $content['lastname']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testUpdatingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->putRequest('/api/users/' . $otherCompanyUser->getId(), [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsNotFound();
    }

    public function testUpdatingUserFromAnotherCompanyAsSuperAdminReturnsUpdatedData(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->putRequest('/api/users/' . $otherCompanyUser->getId(), [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsOk();
    }

    public function testDeletingExistingUserReturnsNoContent(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->deleteRequest('/api/users/' . $user->getId());

        self::assertResponseIsNoContent();
    }

    public function testDeletingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testDeletingUserFromAnotherCompanyAsSuperAdminReturnsNoContent(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNoContent();
    }
}
