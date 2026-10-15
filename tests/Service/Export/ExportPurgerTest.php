<?php

namespace App\Tests\Service\Export;

use App\Entity\ExportJob;
use App\Service\Export\ExportPurger;
use App\Tests\ExportFiles;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ExportPurgerTest extends KernelTestCase
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

    public function testPurgingDeletesTheFileOfAnExportCompletedBeforeTheLimit(): void
    {
        $job = $this->persistedTestObjects->adminWithOwnExportJob()->exportJob;
        $fileName = $this->storeFileFor($job);

        $deleted = $this->purger()->purgeCompletedBefore(new \DateTimeImmutable('+1 day'));

        self::assertSame(1, $deleted);
        self::assertFalse(ExportFiles::in(static::getContainer())->exists($fileName));
        self::assertNull($job->getFileName());
    }

    public function testPurgingKeepsTheFileOfAnExportCompletedAfterTheLimit(): void
    {
        $job = $this->persistedTestObjects->adminWithOwnExportJob()->exportJob;
        $fileName = $this->storeFileFor($job);

        $deleted = $this->purger()->purgeCompletedBefore(new \DateTimeImmutable('-1 day'));

        self::assertSame(0, $deleted);
        self::assertTrue(ExportFiles::in(static::getContainer())->exists($fileName));
        self::assertNotNull($job->getFileName());

        ExportFiles::in(static::getContainer())->delete($fileName);
    }

    public function testPurgingIgnoresAnExportWithoutFile(): void
    {
        $this->persistedTestObjects->adminWithOwnExportJob();

        $deleted = $this->purger()->purgeCompletedBefore(new \DateTimeImmutable('+1 day'));

        self::assertSame(0, $deleted);
    }

    private function purger(): ExportPurger
    {
        return static::getContainer()->get(ExportPurger::class);
    }

    private function storeFileFor(ExportJob $job): string
    {
        $fileName = ExportFiles::in(static::getContainer())->store();

        $job->markAsReady($fileName);
        $this->entityManager->flush();

        return $fileName;
    }
}
