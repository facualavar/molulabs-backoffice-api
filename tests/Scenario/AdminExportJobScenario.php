<?php

namespace App\Tests\Scenario;

use App\Entity\ExportJob;
use App\Entity\User;

final class AdminExportJobScenario
{
    public function __construct(
        public readonly User $admin,
        public readonly ExportJob $exportJob,
    ) {}
}
