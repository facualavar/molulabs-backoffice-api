<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileControllerTest extends CustomWebTestCase
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

    private function encryptPassword(User $user): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
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
        self::assertSame($user->getCompany()->getId(), $content['company']['id']);
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

    public function testUpdatingPasswordWithCorrectCurrentPasswordReturnsNoContent(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->encryptPassword($user);
        $this->loginAs($user);

        $this->putRequest('/api/profile/password', [
            'currentPassword' => 'password',
            'newPassword' => 'new-password',
        ]);

        self::assertResponseIsNoContent();
    }

    public function testUpdatingPasswordWithIncorrectCurrentPasswordReturnsBadRequest(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->encryptPassword($user);
        $this->loginAs($user);

        $this->putRequest('/api/profile/password', [
            'currentPassword' => 'wrong-password',
            'newPassword' => 'new-password',
        ]);

        self::assertResponseIsBadRequest();
    }

    public function testUpdatingPasswordWithoutTokenReturnsUnauthorized(): void
    {
        $this->putRequest('/api/profile/password', [
            'currentPassword' => 'password',
            'newPassword' => 'new-password',
        ]);

        self::assertResponseIsUnauthorized();
    }

    public function testShowingOwnCompanyReturnsCompanyData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->getRequest('/api/profile/company');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($user->getCompany()->getId(), $content['id']);
        self::assertSame($user->getCompany()->getName(), $content['name']);
        self::assertSame($user->getCompany()->getEmail(), $content['email']);
        self::assertSame($user->getCompany()->getDocumentType()->value, $content['documentType']);
        self::assertSame($user->getCompany()->getDocumentNumber(), $content['documentNumber']);
        self::assertSame($user->getCompany()->getPhone(), $content['phone']);
    }

    public function testShowingOwnCompanyAsRegularUserReturnsForbidden(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->getRequest('/api/profile/company');

        self::assertResponseIsForbidden();
    }

    public function testShowingOwnCompanyWithoutTokenReturnsUnauthorized(): void
    {
        $this->getRequest('/api/profile/company');

        self::assertResponseIsUnauthorized();
    }

    public function testUpdatingOwnCompanyReturnsUpdatedData(): void
    {
        $user = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($user);

        $this->putRequest('/api/profile/company', [
            'name' => 'Updated Name',
            'email' => 'updated@company.com',
            'documentType' => 'DNI',
            'documentNumber' => '12345678',
            'phone' => '011-5555-4444',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame('updated@company.com', $content['email']);
        self::assertSame('DNI', $content['documentType']);
        self::assertSame('12345678', $content['documentNumber']);
    }

    public function testUpdatingOwnCompanyAsRegularUserReturnsForbidden(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $this->loginAs($user);

        $this->putRequest('/api/profile/company', [
            'name' => 'Updated Name',
            'email' => 'updated@company.com',
            'documentType' => 'DNI',
            'documentNumber' => '12345678',
            'phone' => '011-5555-4444',
        ]);

        self::assertResponseIsForbidden();
    }

    public function testUpdatingOwnCompanyWithoutTokenReturnsUnauthorized(): void
    {
        $this->putRequest('/api/profile/company', [
            'name' => 'Updated Name',
            'email' => 'updated@company.com',
            'documentType' => 'DNI',
            'documentNumber' => '12345678',
            'phone' => '011-5555-4444',
        ]);

        self::assertResponseIsUnauthorized();
    }
}
