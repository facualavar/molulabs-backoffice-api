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
        $scenario = $this->persistedTestObjects->adminWithExportJobAndCompanyMate(ExportType::USERS);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(ExportStatus::READY, $scenario->exportJob->getStatus());
        self::assertNotNull($scenario->exportJob->getFilePath());
        self::assertFileExists($scenario->exportJob->getFilePath());

        unlink($scenario->exportJob->getFilePath());
    }

    public function testHandlingCompaniesExportMarksJobAsReadyAndWritesFile(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::COMPANIES);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(ExportStatus::READY, $scenario->exportJob->getStatus());
        self::assertNotNull($scenario->exportJob->getFilePath());
        self::assertFileExists($scenario->exportJob->getFilePath());

        unlink($scenario->exportJob->getFilePath());
    }

    public function testHandlingMessageForNonExistentJobDoesNothing(): void
    {
        $handler = static::getContainer()->get(ExportMessageHandler::class);

        $handler(new ExportMessage(999999));

        $this->addToAssertionCount(1);
    }
}
