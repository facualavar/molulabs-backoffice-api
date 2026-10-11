<?php

namespace App\Tests\MessageHandler;

use App\Enum\ExportStatus;
use App\Enum\ExportType;
use App\Message\ExportMessage;
use App\MessageHandler\ExportMessageHandler;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Reader\XLSX\Reader;
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

    public function testHandlingUsersExportFilteredByDocumentNumberOnlyWritesMatchingUsers(): void
    {
        $scenario = $this->persistedTestObjects->adminWithExportJobAndCompanyMate(ExportType::USERS, ['documentNumber' => '30111222'], '30111222');

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame([$scenario->admin->getId()], array_column($this->readDataRows($scenario->exportJob->getFilePath()), 0));
    }

    public function testHandlingCompaniesExportFilteredByDocumentNumberOnlyWritesMatchingCompanies(): void
    {
        $scenario = $this->persistedTestObjects->adminWithCompaniesExportJob(['documentNumber' => '71234567'], ['30-71234567-1', '30-71111111-1']);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(['30-71234567-1'], array_column($this->readDataRows($scenario->exportJob->getFilePath()), 4));
    }

    public function testHandlingMessageForNonExistentJobDoesNothing(): void
    {
        $handler = static::getContainer()->get(ExportMessageHandler::class);

        $handler(new ExportMessage(999999));

        $this->addToAssertionCount(1);
    }

    /**
     * Reads the written file without its header row, and removes it.
     *
     * @return list<list<mixed>>
     */
    private function readDataRows(string $filePath): array
    {
        $reader = new Reader();
        $reader->open($filePath);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();
        unlink($filePath);

        return array_slice($rows, 1);
    }
}
