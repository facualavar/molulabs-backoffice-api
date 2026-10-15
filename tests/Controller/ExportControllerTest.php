<?php

namespace App\Tests\Controller;

use App\Service\Export\ExportPurger;
use App\Tests\ExportFiles;
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

        $fileName = ExportFiles::in(static::getContainer())->store();
        $scenario->exportJob->markAsReady($fileName);
        $this->entityManager->flush();

        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsOk();
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=' . $fileName);
        self::assertSame('fake xlsx content', $this->client->getInternalResponse()->getContent());

        ExportFiles::in(static::getContainer())->delete($fileName);
    }

    public function testDownloadingAnExportWhoseFileWasPurgedReturnsNotFound(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob();

        $fileName = ExportFiles::in(static::getContainer())->store();
        $scenario->exportJob->markAsReady($fileName);
        $this->entityManager->flush();

        static::getContainer()->get(ExportPurger::class)->purgeCompletedBefore(new \DateTimeImmutable('+1 day'));
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsNotFound();
    }

    public function testDownloadingExportJobOwnedByAnotherUserReturnsNotFound(): void
    {
        $scenario = $this->persistedTestObjects->adminWithAnotherUsersExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId() . '/download');

        self::assertResponseIsNotFound();
    }

    public function testShowingExportJobOwnedByAnotherUserRespondsWithTheNotFoundMessage(): void
    {
        $scenario = $this->persistedTestObjects->adminWithAnotherUsersExportJob();
        $this->loginAs($scenario->admin);

        $this->getRequest('/api/exports/' . $scenario->exportJob->getId());

        self::assertStringContainsString('Exportación no encontrada.', $this->client->getResponse()->getContent());
    }
}
