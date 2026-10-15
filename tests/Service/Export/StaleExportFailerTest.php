<?php

namespace App\Tests\Service\Export;

use App\Entity\ExportJob;
use App\Enum\ExportStatus;
use App\Service\Export\StaleExportFailer;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StaleExportFailerTest extends KernelTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;

    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($this->entityManager);
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testFailingMarksAProcessingExportStartedBeforeTheLimit(): void
    {
        $job = $this->processingJob();

        $marked = $this->failer()->failStartedBefore(new \DateTimeImmutable('+1 minute'));

        self::assertSame(1, $marked);
        self::assertSame(ExportStatus::FAILED, $job->getStatus());
        self::assertSame(StaleExportFailer::INTERRUPTED_MESSAGE, $job->getErrorMessage());
        self::assertNotNull($job->getCompletedAt());
    }

    public function testFailingKeepsAProcessingExportStartedAfterTheLimit(): void
    {
        $job = $this->processingJob();

        $marked = $this->failer()->failStartedBefore(new \DateTimeImmutable('-1 minute'));

        self::assertSame(0, $marked);
        self::assertSame(ExportStatus::PROCESSING, $job->getStatus());
    }

    public function testFailingIgnoresAnExportThatIsNotProcessing(): void
    {
        $job = $this->persistedTestObjects->adminWithOwnExportJob()->exportJob;

        $marked = $this->failer()->failStartedBefore(new \DateTimeImmutable('+1 minute'));

        self::assertSame(0, $marked);
        self::assertSame(ExportStatus::PENDING, $job->getStatus());
    }

    private function failer(): StaleExportFailer
    {
        return static::getContainer()->get(StaleExportFailer::class);
    }

    private function processingJob(): ExportJob
    {
        $job = $this->persistedTestObjects->adminWithOwnExportJob()->exportJob;
        $job->markAsProcessing();
        $this->entityManager->flush();

        return $job;
    }
}
