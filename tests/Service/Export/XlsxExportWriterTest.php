<?php

namespace App\Tests\Service\Export;

use App\Entity\ExportJob;
use App\Enum\ExportType;
use App\Service\Export\XlsxExportWriter;
use App\Tests\ExportFiles;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class XlsxExportWriterTest extends KernelTestCase
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

    public function testWritingUsersExportRequestedByRegularAdminExcludesSuperAdmins(): void
    {
        [$admin, $superAdmin, $exportJob] = $this->persistedTestObjects->adminWithSuperAdminCompanyMateAndExportJob(ExportType::USERS);

        $fileName = $this->write($exportJob);

        $ids = array_column($this->files()->takeRows($fileName), 0);

        self::assertContains($admin->getId(), $ids);
        self::assertNotContains($superAdmin->getId(), $ids);
    }

    /**
     * @return iterable<string, array{ExportType}>
     */
    public static function exportTypes(): iterable
    {
        foreach (ExportType::cases() as $type) {
            yield $type->value => [$type];
        }
    }

    #[DataProvider('exportTypes')]
    public function testWritingAnExportOfAnyTypeProducesAFile(ExportType $type): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob($type);

        $fileName = $this->write($scenario->exportJob);

        self::assertTrue($this->files()->exists($fileName));

        $this->files()->delete($fileName);
    }

    public function testWritingAnExportThatFailsLeavesNoFileBehind(): void
    {
        $scenario = $this->persistedTestObjects->adminWithExportJobAndCompanyMate(ExportType::USERS, ['role' => 'ROLE_BOGUS']);
        $writer = static::getContainer()->get(XlsxExportWriter::class);
        $temporaryFilesBefore = glob(sys_get_temp_dir() . '/export_*');

        try {
            $writer->write($scenario->exportJob);
            self::fail('The export was expected to fail.');
        } catch (\ValueError) {
            $storedFiles = glob(static::getContainer()->getParameter('app.storage_directory') . '/exports/users_' . $scenario->exportJob->getId() . '_*');

            self::assertSame([], $storedFiles);
            self::assertSame($temporaryFilesBefore, glob(sys_get_temp_dir() . '/export_*'));
        }
    }

    public function testWritingUsersExportWritesEveryColumnOfTheUser(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::USERS);
        $admin = $scenario->admin;

        $fileName = $this->write($scenario->exportJob);

        $rows = $this->files()->takeRows($fileName);

        self::assertSame(['ID', 'Nombre', 'Apellido', 'Email', 'Rol', 'Empresa'], $rows[0]);
        self::assertSame([
            $admin->getId(),
            $admin->getFirstname(),
            $admin->getLastname(),
            $admin->getEmail(),
            $admin->getRole()->value,
            $admin->getCompany()->getName(),
        ], $rows[1]);

    }

    public function testWritingCompaniesExportWritesEveryColumnOfTheCompany(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::COMPANIES);
        $company = $scenario->admin->getCompany();

        $fileName = $this->write($scenario->exportJob);

        $rows = $this->files()->takeRows($fileName);

        self::assertSame(['ID', 'Nombre', 'Email', 'Tipo de Documento', 'Número de Documento', 'Teléfono', 'Fecha de Creación'], $rows[0]);
        self::assertSame([
            $company->getId(),
            $company->getName(),
            $company->getEmail(),
            $company->getDocumentType()->value,
            $company->getDocumentNumber(),
            $company->getPhone(),
            $company->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $rows[1]);

    }

    private function write(ExportJob $job): string
    {
        return static::getContainer()->get(XlsxExportWriter::class)->write($job);
    }

    private function files(): ExportFiles
    {
        return ExportFiles::in(static::getContainer());
    }
}
