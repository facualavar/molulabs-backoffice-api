<?php

namespace App\Tests\Dto;

use App\Dto\Company\CompanyExportFilters;
use App\Dto\Company\CompanyFilterRequest;
use App\Enum\ExportType;
use PHPUnit\Framework\TestCase;

final class CompanyExportFiltersTest extends TestCase
{
    public function testItBelongsToTheCompaniesExport(): void
    {
        self::assertSame(ExportType::COMPANIES, (new CompanyExportFilters())->type());
    }

    public function testRebuildingStoredFiltersGivesBackTheSameFilters(): void
    {
        $filters = new CompanyExportFilters('Acme', '30-71234567-1');

        $rebuilt = CompanyExportFilters::fromArray($filters->toArray());

        self::assertEquals($filters, $rebuilt);
    }

    public function testRebuildingFiltersWithMissingKeysLeavesThemEmpty(): void
    {
        $filters = CompanyExportFilters::fromArray([]);

        self::assertEquals(new CompanyExportFilters(), $filters);
    }

    public function testBuildingFiltersFromTheListRequestKeepsOnlyTheFilters(): void
    {
        $request = new CompanyFilterRequest('Acme', '3071', 3, 50);

        self::assertEquals(new CompanyExportFilters('Acme', '3071'), $request->toExportFilters());
    }
}
