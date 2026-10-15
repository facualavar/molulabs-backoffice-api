<?php

namespace App\Dto\Export;

use App\Enum\ExportType;

interface ExportFilters
{
    public function type(): ExportType;

    /**
     * @return array<string, mixed> what ExportJob stores, so the worker can rebuild these filters
     */
    public function toArray(): array;
}
