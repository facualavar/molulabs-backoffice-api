<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class ExportControllerTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($this->entityManager);
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testShowingOwnExportJobReturnsData(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId());

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($scenario->exportJob->getId(), $content['id']);
        self::assertSame('users', $content['type']);
        self::assertSame('pending', $content['status']);
    }

    public function testShowingExportJobOwnedByAnotherUserReturnsNotFound(): void
    {
        $scenario = $this->persistedTestObjects->adminWithAnotherUsersExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId());

        self::assertResponseIsNotFound();
    }

    public function testShowingExportJobWithoutTokenReturnsUnauthorized(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId());

        self::assertResponseIsUnauthorized();
    }

    public function testDownloadingPendingExportJobReturnsNotFound(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsNotFound();
    }

    public function testDownloadingReadyExportJobReturnsFile(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();

        $filePath = tempnam(sys_get_temp_dir(), 'export_test_');
        file_put_contents($filePath, 'fake xlsx content');
        $scenario->exportJob->markAsReady($filePath);
        $this->entityManager->flush();

        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsOk();
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=' . basename($filePath));

        unlink($filePath);
    }

    public function testDownloadingExportJobOwnedByAnotherUserReturnsNotFound(): void
    {
        $scenario = $this->persistedTestObjects->adminWithAnotherUsersExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsNotFound();
    }
}
