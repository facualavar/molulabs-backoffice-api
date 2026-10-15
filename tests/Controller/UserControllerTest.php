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
        $this->loginAsAdmin();

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
        $this->loginAsAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testShowingUserFromAnotherCompanyAsSuperAdminReturnsItsData(): void
    {
        $this->loginAsSuperAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->getRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsOk();
    }

    public function testListingUsersFilteredByNameReturnsMatchingUsers(): void
    {
        $admin = $this->loginAsAdmin();
        [$matchingUser] = $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
            ['firstname' => 'Bruce', 'company' => $admin->getCompany()],
        ]);

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

    public function testListingUsersFilteredByDocumentNumberReturnsMatchingUsers(): void
    {
        $this->loginAsSuperAdmin();
        [$matchingUser] = $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'documentNumber' => '11223344'],
            ['firstname' => 'Bruce', 'documentNumber' => '44332211'],
        ]);

        $this->getRequest('/api/users?documentNumber=11223344');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersFilteredByNonMatchingDocumentNumberReturnsEmptyList(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->getUser('Wanda', null, '11223344');

        $this->getRequest('/api/users?documentNumber=00000000');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content['data']);
    }

    public function testListingUsersRespectsLimit(): void
    {
        $admin = $this->loginAsAdmin();
        $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
            ['firstname' => 'Bruce', 'company' => $admin->getCompany()],
        ]);

        $this->getRequest('/api/users?limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersRespectsPage(): void
    {
        $admin = $this->loginAsAdmin();
        [, $lastUser] = $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
            ['firstname' => 'Bruce', 'company' => $admin->getCompany()],
        ]);

        $this->getRequest('/api/users?page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($lastUser->getId(), $content['data'][0]['id']);
    }

    public function testListingUsersReturnsPaginationMeta(): void
    {
        $admin = $this->loginAsAdmin();
        $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
            ['firstname' => 'Bruce', 'company' => $admin->getCompany()],
        ]);

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
        $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
            ['firstname' => 'Bruce'],
        ]);

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingUsersAsRegularAdminExcludesSuperAdmins(): void
    {
        [$admin, $superAdmin] = $this->persistedTestObjects->adminWithSuperAdminCompanyMate();
        $this->loginAs($admin);

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        $ids = array_column($content['data'], 'id');
        self::assertNotContains($superAdmin->getId(), $ids);
    }

    public function testListingUsersAsSuperAdminIncludesOtherSuperAdmins(): void
    {
        [$loggedInSuperAdmin, $otherSuperAdmin] = $this->persistedTestObjects->twoSuperAdmins();
        $this->loginAs($loggedInSuperAdmin);

        $this->getRequest('/api/users?limit=20');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        $ids = array_column($content['data'], 'id');
        self::assertContains($otherSuperAdmin->getId(), $ids);
    }

    public function testListingUsersAsSuperAdminReturnsUsersFromAllCompanies(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->users([
            ['firstname' => 'Wanda'],
            ['firstname' => 'Bruce'],
        ]);

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

    public function testExportingUsersKeepsListFilters(): void
    {
        $this->loginAsAdmin();

        $this->postRequest('/api/users/export?name=Wanda&role=ROLE_USER&documentNumber=3011');

        self::assertResponseIsAccepted();
        self::assertSame(
            ['name' => 'Wanda', 'role' => 'ROLE_USER', 'documentNumber' => '3011'],
            $this->findExportJob($this->getResponseContent()['id'])->getFilters(),
        );
    }

    public function testExportingUsersAsRegularUserReturnsForbidden(): void
    {
        $this->loginAsRegularUser();

        $this->postRequest('/api/users/export');

        self::assertResponseIsForbidden();
    }

    public function testListingUsersAsRegularUserReturnsForbidden(): void
    {
        $this->loginAsRegularUser();

        $this->getRequest('/api/users');

        self::assertResponseIsForbidden();
    }

    public function testCreatingUserAsRegularUserReturnsForbidden(): void
    {
        $this->loginAsRegularUser();
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
            'documentNumber' => '99999999',
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

    public function testCreatingUserWithDuplicateDocumentNumberReturnsConflict(): void
    {
        $actingUser = $this->loginAsAdmin();
        $existingUser = $this->persistedTestObjects->getUser('Wanda', $actingUser->getCompany());

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'documentNumber' => $existingUser->getDocumentNumber(),
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsConflict();

        $content = $this->getResponseContent();
        self::assertSame('documentNumber', $content['errors'][0]['field']);
    }

    public function testCreatingUserWithDuplicateEmailReturnsConflict(): void
    {
        $actingUser = $this->loginAsAdmin();
        $existingUser = $this->persistedTestObjects->getUser('Wanda', $actingUser->getCompany());

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => $existingUser->getEmail(),
            'documentNumber' => '99999999',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsConflict();

        $content = $this->getResponseContent();
        self::assertSame('email', $content['errors'][0]['field']);
    }

    public function testCreatingUserWithSuperAdminRoleReturnsBadRequest(): void
    {
        $superAdmin = $this->loginAsSuperAdmin();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'documentNumber' => '99999999',
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
            'documentNumber' => '99999999',
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
        $this->loginAsSuperAdmin();
        $otherCompany = $this->persistedTestObjects->getCompany();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'documentNumber' => '99999999',
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
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/users', [
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => 'new@company.com',
            'documentNumber' => '99999999',
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
            'documentNumber' => '99999999',
            'password' => 'password',
            'role' => 'ROLE_ADMIN',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated', $content['firstname']);
        self::assertSame('Name', $content['lastname']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testUpdatingUserKeepingItsEmailAndDocumentNumberReturnsUpdatedData(): void
    {
        $user = $this->loginAsAdmin();

        $this->putRequest('/api/users/' . $user->getId(), [
            'firstname' => 'Updated',
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'documentNumber' => $user->getDocumentNumber(),
            'password' => 'password',
            'role' => 'ROLE_ADMIN',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated', $content['firstname']);
        self::assertSame($user->getEmail(), $content['email']);
    }

    public function testUpdatingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $this->loginAsAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->putRequest('/api/users/' . $otherCompanyUser->getId(), [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
            'documentNumber' => '99999999',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsNotFound();
    }

    public function testUpdatingUserFromAnotherCompanyAsSuperAdminReturnsUpdatedData(): void
    {
        $this->loginAsSuperAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->putRequest('/api/users/' . $otherCompanyUser->getId(), [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'email' => 'updated@company.com',
            'documentNumber' => '99999999',
            'password' => 'password',
            'role' => 'ROLE_USER',
        ]);

        self::assertResponseIsOk();
    }

    public function testDeletingExistingUserReturnsNoContent(): void
    {
        $admin = $this->loginAsAdmin();
        [$companyMate] = $this->persistedTestObjects->users([
            ['firstname' => 'Wanda', 'company' => $admin->getCompany()],
        ]);

        $this->deleteRequest('/api/users/' . $companyMate->getId());

        self::assertResponseIsNoContent();
    }

    public function testDeletingOwnUserReturnsBadRequest(): void
    {
        $admin = $this->loginAsAdmin();

        $this->deleteRequest('/api/users/' . $admin->getId());

        self::assertResponseIsBadRequest();
    }

    public function testDeletingUserFromAnotherCompanyReturnsNotFound(): void
    {
        $this->loginAsAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNotFound();
    }

    public function testDeletingUserFromAnotherCompanyAsSuperAdminReturnsNoContent(): void
    {
        $this->loginAsSuperAdmin();
        $otherCompanyUser = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/users/' . $otherCompanyUser->getId());

        self::assertResponseIsNoContent();
    }

    public function testDeletingUserWithExportJobsReturnsConflict(): void
    {
        $this->loginAsSuperAdmin();
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();

        $this->deleteRequest('/api/users/' . $scenario->admin->getId());

        self::assertResponseIsConflict();
    }

    public function testShowingSuperAdminFromOwnCompanyAsRegularAdminReturnsNotFound(): void
    {
        [$admin, $superAdmin] = $this->persistedTestObjects->adminWithSuperAdminCompanyMate();
        $this->loginAs($admin);

        $this->getRequest('/api/users/' . $superAdmin->getId());

        self::assertResponseIsNotFound();
    }

    public function testUpdatingSuperAdminFromOwnCompanyAsRegularAdminReturnsNotFound(): void
    {
        [$admin, $superAdmin] = $this->persistedTestObjects->adminWithSuperAdminCompanyMate();
        $this->loginAs($admin);

        $this->putRequest('/api/users/' . $superAdmin->getId(), [
            'firstname' => 'Updated',
            'lastname' => $superAdmin->getLastname(),
            'email' => $superAdmin->getEmail(),
            'documentNumber' => $superAdmin->getDocumentNumber(),
            'password' => 'password',
            'role' => 'ROLE_SUPERADMIN',
        ]);

        self::assertResponseIsNotFound();
    }

    public function testDeletingSuperAdminFromOwnCompanyAsRegularAdminReturnsNotFound(): void
    {
        [$admin, $superAdmin] = $this->persistedTestObjects->adminWithSuperAdminCompanyMate();
        $this->loginAs($admin);

        $this->deleteRequest('/api/users/' . $superAdmin->getId());

        self::assertResponseIsNotFound();
    }

    public function testShowingAnotherSuperAdminAsSuperAdminReturnsOk(): void
    {
        [$superAdmin, $otherSuperAdmin] = $this->persistedTestObjects->twoSuperAdmins();
        $this->loginAs($superAdmin);

        $this->getRequest('/api/users/' . $otherSuperAdmin->getId());

        self::assertResponseIsOk();
    }
}
