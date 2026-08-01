<?php

namespace App\Tests\MessageHandler;

use App\Enum\ExportStatus;
use App\Enum\ExportType;
use App\Message\ExportMessage;
use App\MessageHandler\ExportMessageHandler;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ExportMessageHandlerTest extends KernelTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($entityManager);
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        parent::tearDown();
    }

    public function testHandlingUsersExportMarksJobAsReadyAndWritesFile(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->persistedTestObjects->getUser('Wanda', $admin->getCompany());
        $exportJob = $this->persistedTestObjects->getExportJob($admin, ExportType::USERS);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($exportJob->getId()));

        self::assertSame(ExportStatus::READY, $exportJob->getStatus());
        self::assertNotNull($exportJob->getFilePath());
        self::assertFileExists($exportJob->getFilePath());

        unlink($exportJob->getFilePath());
    }

    public function testHandlingCompaniesExportMarksJobAsReadyAndWritesFile(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $exportJob = $this->persistedTestObjects->getExportJob($admin, ExportType::COMPANIES);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($exportJob->getId()));

        self::assertSame(ExportStatus::READY, $exportJob->getStatus());
        self::assertNotNull($exportJob->getFilePath());
        self::assertFileExists($exportJob->getFilePath());

        unlink($exportJob->getFilePath());
    }

    public function testHandlingMessageForNonExistentJobDoesNothing(): void
    {
        $handler = static::getContainer()->get(ExportMessageHandler::class);

        $handler(new ExportMessage(999999));

        $this->addToAssertionCount(1);
    }
}
