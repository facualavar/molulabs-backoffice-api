<?php

namespace App\Tests\MessageHandler;

use App\Entity\ExportJob;
use App\Enum\ExportStatus;
use App\Enum\ExportType;
use App\Message\ExportMessage;
use App\MessageHandler\ExportMessageHandler;
use App\Tests\ExportFiles;
use App\Tests\PersistedTestObjects;
use App\Tests\TestObjects;
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
        self::assertNotNull($scenario->exportJob->getFileName());
        self::assertTrue($this->files()->exists($scenario->exportJob->getFileName()));

        $this->files()->delete($scenario->exportJob->getFileName());
    }

    public function testHandlingCompaniesExportMarksJobAsReadyAndWritesFile(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::COMPANIES);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(ExportStatus::READY, $scenario->exportJob->getStatus());
        self::assertNotNull($scenario->exportJob->getFileName());
        self::assertTrue($this->files()->exists($scenario->exportJob->getFileName()));

        $this->files()->delete($scenario->exportJob->getFileName());
    }

    public function testHandlingUsersExportFilteredByDocumentNumberOnlyWritesMatchingUsers(): void
    {
        $scenario = $this->persistedTestObjects->adminWithExportJobAndCompanyMate(ExportType::USERS, ['documentNumber' => '30111222'], '30111222');

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame([$scenario->admin->getId()], array_column($this->dataRowsOf($scenario->exportJob), 0));
    }

    public function testHandlingCompaniesExportFilteredByDocumentNumberOnlyWritesMatchingCompanies(): void
    {
        $scenario = $this->persistedTestObjects->adminWithCompaniesExportJob(['documentNumber' => '71234567'], ['30-71234567-1', '30-71111111-1']);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(['30-71234567-1'], array_column($this->dataRowsOf($scenario->exportJob), 4));
    }

    public function testHandlingTwoExportsOfTheSameTypeWritesDifferentFiles(): void
    {
        $first = $this->persistedTestObjects->adminWithOwnExportJob();
        $secondJob = TestObjects::getExportJob($first->admin);
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($secondJob);
        $entityManager->flush();

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($first->exportJob->getId()));
        $handler(new ExportMessage($secondJob->getId()));

        self::assertNotSame($first->exportJob->getFileName(), $secondJob->getFileName());
        self::assertStringContainsString('_' . $first->exportJob->getId() . '_', $first->exportJob->getFileName());

        $this->files()->delete($first->exportJob->getFileName());
        $this->files()->delete($secondJob->getFileName());
    }

    public function testHandlingExportThatFailsMarksJobAsFailedWithoutExposingTheError(): void
    {
        $scenario = $this->persistedTestObjects->adminWithExportJobAndCompanyMate(ExportType::USERS, ['role' => 'ROLE_BOGUS']);

        $handler = static::getContainer()->get(ExportMessageHandler::class);
        $handler(new ExportMessage($scenario->exportJob->getId()));

        self::assertSame(ExportStatus::FAILED, $scenario->exportJob->getStatus());
        self::assertSame('No se pudo generar la exportación.', $scenario->exportJob->getErrorMessage());
        self::assertNotNull($scenario->exportJob->getCompletedAt());
    }

    public function testHandlingMessageForNonExistentJobDoesNothing(): void
    {
        $handler = static::getContainer()->get(ExportMessageHandler::class);

        $handler(new ExportMessage(999999));

        $this->addToAssertionCount(1);
    }

    private function files(): ExportFiles
    {
        return ExportFiles::in(static::getContainer());
    }

    /**
     * Reads the written file without its header row, and removes it.
     *
     * @return list<list<mixed>>
     */
    private function dataRowsOf(ExportJob $job): array
    {
        return array_slice($this->files()->takeRows($job->getFileName()), 1);
    }
}
