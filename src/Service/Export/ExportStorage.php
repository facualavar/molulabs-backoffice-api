<?php

namespace App\Service\Export;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Where the export files live: a local directory in development, S3 in production. Jobs store the
 * file name only, so the location can change without leaving broken paths in the database.
 */
final class ExportStorage
{
    public function __construct(
        #[Autowire(service: 'exports.storage')] private readonly FilesystemOperator $storage,
    ) {}

    public function upload(string $fileName, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');

        try {
            $this->storage->writeStream($fileName, $stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return resource
     *
     * @throws UnableToReadFile when the file is not in the storage
     */
    public function readStream(string $fileName)
    {
        return $this->storage->readStream($fileName);
    }

    public function delete(string $fileName): void
    {
        $this->storage->delete($fileName);
    }
}
