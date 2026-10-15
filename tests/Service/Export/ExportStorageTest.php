<?php

namespace App\Tests\Service\Export;

use App\Service\Export\ExportStorage;
use League\Flysystem\UnableToReadFile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ExportStorageTest extends KernelTestCase
{
    private ExportStorage $storage;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->storage = static::getContainer()->get(ExportStorage::class);
    }

    public function testAnUploadedFileCanBeReadBack(): void
    {
        $fileName = 'storage_test_' . uniqid() . '.xlsx';

        $this->storage->upload($fileName, $this->localFile('xlsx content'));

        self::assertSame('xlsx content', stream_get_contents($this->storage->readStream($fileName)));

        $this->storage->delete($fileName);
    }

    public function testADeletedFileNoLongerExists(): void
    {
        $fileName = 'storage_test_' . uniqid() . '.xlsx';
        $this->storage->upload($fileName, $this->localFile('xlsx content'));

        $this->storage->delete($fileName);

        $this->expectException(UnableToReadFile::class);
        $this->storage->readStream($fileName);
    }

    public function testDeletingAFileThatDoesNotExistDoesNothing(): void
    {
        $this->storage->delete('storage_test_missing.xlsx');

        $this->expectException(UnableToReadFile::class);
        $this->storage->readStream('storage_test_missing.xlsx');
    }

    private function localFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'storage_test_');
        file_put_contents($path, $content);

        return $path;
    }
}
