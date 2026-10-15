<?php

namespace App\Tests;

use App\Service\Export\ExportStorage;
use League\Flysystem\UnableToReadFile;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The export files of the test storage, seen from a test.
 */
final class ExportFiles
{
    public function __construct(private readonly ExportStorage $storage) {}

    public static function in(ContainerInterface $container): self
    {
        return new self($container->get(ExportStorage::class));
    }

    /**
     * @return string the name of the stored file
     */
    public function store(string $content = 'fake xlsx content'): string
    {
        $localPath = $this->tempFile();
        file_put_contents($localPath, $content);

        $fileName = 'export_test_' . uniqid() . '.xlsx';
        $this->storage->upload($fileName, $localPath);

        unlink($localPath);

        return $fileName;
    }

    public function exists(string $fileName): bool
    {
        try {
            fclose($this->storage->readStream($fileName));

            return true;
        } catch (UnableToReadFile) {
            return false;
        }
    }

    public function delete(string $fileName): void
    {
        $this->storage->delete($fileName);
    }

    /**
     * Reads the spreadsheet, header row included, and removes it from the storage.
     *
     * @return list<list<scalar>>
     */
    public function takeRows(string $fileName): array
    {
        $localPath = $this->tempFile();
        file_put_contents($localPath, $this->storage->readStream($fileName));

        $reader = new Reader();
        $reader->open($localPath);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }

        $reader->close();
        unlink($localPath);
        $this->storage->delete($fileName);

        return $rows;
    }

    private function tempFile(): string
    {
        return tempnam(sys_get_temp_dir(), 'export_test_');
    }
}
