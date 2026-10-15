<?php

namespace App\Service\Export;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Where the export files live. Jobs store the file name only, so the directory can move without
 * leaving broken paths in the database.
 */
final class ExportStorage
{
    public function __construct(private readonly string $exportDirectory) {}

    public function pathOf(string $fileName): string
    {
        return rtrim($this->exportDirectory, '/') . '/' . $fileName;
    }

    public function prepare(): void
    {
        (new Filesystem())->mkdir($this->exportDirectory);
    }
}
