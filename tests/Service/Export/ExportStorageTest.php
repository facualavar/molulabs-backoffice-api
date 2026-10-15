<?php

namespace App\Tests\Service\Export;

use App\Service\Export\ExportStorage;
use PHPUnit\Framework\TestCase;

final class ExportStorageTest extends TestCase
{
    public function testResolvingAFileNameJoinsItToTheDirectory(): void
    {
        $storage = new ExportStorage('/var/exports');

        self::assertSame('/var/exports/users_1.xlsx', $storage->pathOf('users_1.xlsx'));
    }

    public function testResolvingAFileNameIgnoresTheTrailingSlashOfTheDirectory(): void
    {
        $storage = new ExportStorage('/var/exports/');

        self::assertSame('/var/exports/users_1.xlsx', $storage->pathOf('users_1.xlsx'));
    }

    public function testPreparingCreatesTheDirectory(): void
    {
        $directory = sys_get_temp_dir() . '/export_storage_' . uniqid();
        $storage = new ExportStorage($directory);

        $storage->prepare();

        self::assertDirectoryExists($directory);

        rmdir($directory);
    }
}
