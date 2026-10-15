<?php

namespace App\Tests\Dto;

use App\Dto\User\UserExportFilters;
use App\Dto\User\UserFilterRequest;
use App\Enum\ExportType;
use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserExportFiltersTest extends TestCase
{
    public function testItBelongsToTheUsersExport(): void
    {
        self::assertSame(ExportType::USERS, (new UserExportFilters())->type());
    }

    public function testRebuildingStoredFiltersGivesBackTheSameFilters(): void
    {
        $filters = new UserExportFilters('Wanda', UserRole::ADMIN, '3011');

        $rebuilt = UserExportFilters::fromArray($filters->toArray());

        self::assertEquals($filters, $rebuilt);
    }

    public function testStoringFiltersWritesTheRoleAsItsValue(): void
    {
        $filters = new UserExportFilters('Wanda', UserRole::ADMIN, '3011');

        self::assertSame(['name' => 'Wanda', 'role' => 'ROLE_ADMIN', 'documentNumber' => '3011'], $filters->toArray());
    }

    public function testRebuildingFiltersWithMissingKeysLeavesThemEmpty(): void
    {
        $filters = UserExportFilters::fromArray([]);

        self::assertEquals(new UserExportFilters(), $filters);
    }

    public function testBuildingFiltersFromTheListRequestKeepsOnlyTheFilters(): void
    {
        $request = new UserFilterRequest('Wanda', UserRole::USER, '3011', 3, 50);

        self::assertEquals(new UserExportFilters('Wanda', UserRole::USER, '3011'), $request->toExportFilters());
    }
}
