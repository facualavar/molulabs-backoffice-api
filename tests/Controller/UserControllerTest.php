<?php

namespace App\Tests\Controller;

use App\Entity\User;
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

    private function loginAsAdmin(): User
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);

        return $admin;
    }

    private function loginAsSuperAdmin(): User
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $this->loginAs($superAdmin);

        return $superAdmin;
    }

    private function loginAsRegularUser(): User
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        return $user;
    }

    public function testListingUsersReturnsSuccessfulResponse(): void
    {
        $user = $this->loginAsAdmin();

        $this->getRequest('/api/users');

        self::assertResponseIsOk();
    }

    public function testShowingExistingUserReturnsItsData(): void
    {
        $user = $this->loginAsAdmin();

        $this->getRequest('/api/users/' . $user->getId());

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getFirstname(), $content['firstname']);
        self::assertSame($user->getLastname(), $content['lastname']);
        self::assertSame($user->getEmail(), $content['email']);
        self::assertSame($user->getCompany()->getId(), $content['company']['id']);
        self::assertSame($user->getCompany()->getName(), $content['company']['name']);
    }

    public function testShowingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $admin = $this->loginAsAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testShowingUserFromAnotherCompanyAsSuperAdminReturnsItsData(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsOk();
    }

    public function testListingUsersFilteredByNameReturnsMatchingUsers(): void
    {
        $admin = $this->loginAsAdmin();
        $matchingUser = $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?name=Wanda');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersFilteredByRoleReturnsMatchingUsers(): void
    {
        $admin = $this->loginAsAdmin();
        $regularUser = $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());

        $this->getRequest('/api/users?role=ROLE_USER');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($regularUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersRespectsLimit(): void
    {
        $admin = $this->loginAsAdmin();
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce', $admin->getCompany());

        $this->getRequest('/api/users?limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersRespectsPage(): void
    {
        $admin = $this->loginAsAdmin();
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
        $admin = $this->loginAsAdmin();
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
        $admin = $this->loginAsAdmin();
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $this->persistedTestObjects->getUser('Bruce');

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersAsSuperAdminReturnsUsersFromAllCompanies(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();
        $this->persistedTestObjects->getUser('Wanda');
        $this->persistedTestObjects->getUser('Bruce');

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(3, $content['data']);
    }

    public function testExportingUsersReturnsAcceptedWithPendingJob(): void
    {
        $this->loginAsAdmin();

        $this->postRequest('/api/users/export');

        self::assertResponseIsAccepted();

        $content = $this->getResponseContent();
        self::assertSame('users', $content['type']);
        self::assertSame('pending', $content['status']);
    }

    public function testExportingUsersAsRegularUserReturnsForbidden(): void
    {
        $this->loginAsRegularUser();

        $this->postRequest('/api/users/export');

        self::assertResponseIsForbidden();
    }

    public function testListingUsersAsRegularUserReturnsForbidden(): void
    {
        $user = $this->loginAsRegularUser();

        $this->getRequest('/api/users');

        self::assertResponseIsForbidden();
    }

    public function testCreatingUserAsRegularUserReturnsForbidden(): void
    {
        $actingUser = $this->loginAsRegularUser();
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
        $actingUser = $this->loginAsAdmin();

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
        self::assertSame($actingUser->getCompany()->getId(), $content['company']['id']);
    }

    public function testCreatingUserWithSuperAdminRoleReturnsBadRequest(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();

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
        $actingUser = $this->loginAsAdmin();
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
        self::assertSame($actingUser->getCompany()->getId(), $content['company']['id']);
    }

    public function testCreatingUserAsSuperAdminForOtherCompanyReturnsCreatedUser(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();
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
        self::assertSame($otherCompany->getId(), $content['company']['id']);
    }

    public function testCreatingUserAsSuperAdminWithNonExistentCompanyReturnsNotFound(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();

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
        $user = $this->loginAsAdmin();

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
        $admin = $this->loginAsAdmin();
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
        $superAdmin = $this->loginAsSuperAdmin();
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
        $user = $this->loginAsAdmin();

        $this->deleteRequest('/api/users/' . $user->getId());

        self::assertResponseIsNoContent();
    }

    public function testDeletingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $admin = $this->loginAsAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testDeletingUserFromAnotherCompanyAsSuperAdminReturnsNoContent(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNoContent();
    }
}
