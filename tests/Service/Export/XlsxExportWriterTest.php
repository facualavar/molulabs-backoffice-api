<?php

namespace App\Tests\Service\Export;

use App\Enum\ExportType;
use App\Service\Export\XlsxExportWriter;
use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Reader\XLSX\Reader;
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
