<?php

namespace App\Service\Export;

use App\Entity\ExportJob;
use App\Enum\ExportType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface Exporter
{
    public const string TAG = 'app.exporter';

    public function type(): ExportType;

    /**
     * @return list<string>
     */
    public function headers(): array;

    /**
     * @return iterable<list<scalar>>
     */
    public function rows(ExportJob $job): iterable;
}
