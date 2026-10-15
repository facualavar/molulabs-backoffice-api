<?php

namespace App\Service\Export;

use App\Dto\Company\CompanyExportFilters;
use App\Entity\ExportJob;
use App\Enum\ExportType;
use App\Repository\CompanyRepository;

final class CompanyExporter implements Exporter
{
    public function __construct(private readonly CompanyRepository $companyRepository) {}

    public function type(): ExportType
    {
        return ExportType::COMPANIES;
    }

    public function headers(): array
    {
        return ['ID', 'Nombre', 'Email', 'Tipo de Documento', 'Número de Documento', 'Teléfono', 'Fecha de Creación'];
    }

    public function rows(ExportJob $job): iterable
    {
        $filters = CompanyExportFilters::fromArray($job->getFilters());

        foreach ($this->companyRepository->iterateExportRowsByFilters($filters->name, $filters->documentNumber) as $company) {
            yield [
                $company['id'],
                $company['name'],
                $company['email'],
                $company['documentType']->value,
                $company['documentNumber'],
                $company['phone'],
                $company['createdAt']->format('Y-m-d H:i:s'),
            ];
        }
    }
}
