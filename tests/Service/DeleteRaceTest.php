<?php

namespace App\Tests\Service;

use App\Exception\CompanyHasUsersException;
use App\Exception\UserHasExportJobsException;
use App\Service\CompanyService;
use App\Service\UserService;
use App\Tests\PersistedTestObjects;
use App\Tests\TestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Between the check that nothing references a record and its deletion, a concurrent request can
 * insert a reference to it. The rival request is played by an entity persisted but not flushed
 * yet: the check does not see it, and it reaches the database in the same flush as the deletion.
 */
final class DeleteRaceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($this->entityManager);
    }

    public function testDeletingCompanyThatGainsAUserConcurrentlyThrowsCompanyHasUsers(): void
    {
        $company = $this->persistedTestObjects->getCompany();
        $this->entityManager->persist(TestObjects::getUser($company));

        $this->expectException(CompanyHasUsersException::class);

        static::getContainer()->get(CompanyService::class)->delete($company);
    }

    public function testDeletingUserThatGainsAnExportJobConcurrentlyThrowsUserHasExportJobs(): void
    {
        $user = $this->persistedTestObjects->getUser();
        $currentUser = $this->persistedTestObjects->getAdminUser();
        $this->entityManager->persist(TestObjects::getExportJob($user));

        $this->expectException(UserHasExportJobsException::class);

        static::getContainer()->get(UserService::class)->delete($user, $currentUser);
    }
}
