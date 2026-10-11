<?php

namespace App\Tests\Service;

use App\Enum\DocumentType;
use App\Enum\UserRole;
use App\Exception\DuplicateDocumentNumberException;
use App\Exception\DuplicateEmailException;
use App\Service\CompanyService;
use App\Service\UserService;
use App\Tests\PersistedTestObjects;
use App\Tests\TestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Two requests can both pass the lookup for an existing value before either inserts it. The rival
 * request is played by an entity persisted but not flushed yet: the lookup does not see it, and
 * both rows reach the unique index in the same flush.
 */
final class DuplicateRaceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($this->entityManager);
    }

    public function testCreatingCompanyWithDocumentNumberInsertedConcurrentlyThrowsDuplicateDocumentNumber(): void
    {
        $rival = TestObjects::getCompany();
        $this->entityManager->persist($rival);

        $this->expectException(DuplicateDocumentNumberException::class);

        $this->service(CompanyService::class)->create('Other', 'other@company.com', DocumentType::CUIT, $rival->getDocumentNumber(), '011-4444-5555');
    }

    public function testCreatingUserWithEmailInsertedConcurrentlyThrowsDuplicateEmail(): void
    {
        $company = $this->persistedTestObjects->getCompany();
        $rival = TestObjects::getUser($company);
        $this->entityManager->persist($rival);

        $this->expectException(DuplicateEmailException::class);

        $this->service(UserService::class)->create($company, 'Ana', 'Paz', $rival->getEmail(), '30111222', 'password', UserRole::USER);
    }

    public function testCreatingUserWithDocumentNumberInsertedConcurrentlyThrowsDuplicateDocumentNumber(): void
    {
        $company = $this->persistedTestObjects->getCompany();
        $this->entityManager->persist(TestObjects::getUser($company, documentNumber: '30111222'));

        $this->expectException(DuplicateDocumentNumberException::class);

        $this->service(UserService::class)->create($company, 'Ana', 'Paz', 'ana@company.com', '30111222', 'password', UserRole::USER);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(string $class): object
    {
        return static::getContainer()->get($class);
    }
}
