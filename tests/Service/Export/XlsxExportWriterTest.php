<?php

namespace App\Tests\Service\Export;

use App\Enum\ExportType;
use App\Service\Export\XlsxExportWriter;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Reader\XLSX\Reader;
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

        $writer = static::getContainer()->get(XlsxExportWriter::class);
        $filePath = $writer->write($exportJob);

        $ids = array_column($this->readRows($filePath), 0);

        self::assertContains($admin->getId(), $ids);
        self::assertNotContains($superAdmin->getId(), $ids);

        unlink($filePath);
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

        $writer = static::getContainer()->get(XlsxExportWriter::class);
        $filePath = $writer->write($scenario->exportJob);

        self::assertFileExists($filePath);

        unlink($filePath);
    }

    public function testWritingUsersExportWritesEveryColumnOfTheUser(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::USERS);
        $admin = $scenario->admin;

        $writer = static::getContainer()->get(XlsxExportWriter::class);
        $filePath = $writer->write($scenario->exportJob);

        $rows = $this->readRows($filePath);

        self::assertSame(['ID', 'Nombre', 'Apellido', 'Email', 'Rol', 'Empresa'], $rows[0]);
        self::assertSame([
            $admin->getId(),
            $admin->getFirstname(),
            $admin->getLastname(),
            $admin->getEmail(),
            $admin->getRole()->value,
            $admin->getCompany()->getName(),
        ], $rows[1]);

        unlink($filePath);
    }

    public function testWritingCompaniesExportWritesEveryColumnOfTheCompany(): void
    {
        $scenario = $this->persistedTestObjects->adminWithOwnExportJob(ExportType::COMPANIES);
        $company = $scenario->admin->getCompany();

        $writer = static::getContainer()->get(XlsxExportWriter::class);
        $filePath = $writer->write($scenario->exportJob);

        $rows = $this->readRows($filePath);

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

        unlink($filePath);
    }

    /**
     * @return list<list<scalar>>
     */
    private function readRows(string $filePath): array
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

        return $rows;
    }
}
