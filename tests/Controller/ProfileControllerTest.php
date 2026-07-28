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
}
