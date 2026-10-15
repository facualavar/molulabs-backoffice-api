<?php

namespace App\Service\Export;

use App\Dto\Company\CompanyExportFilters;
use App\Dto\User\UserExportFilters;
use App\Entity\ExportJob;
use App\Enum\ExportType;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\Filesystem\Filesystem;

final class XlsxExportWriter
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly UserRepository $userRepository,
        private readonly string $exportDirectory,
    ) {}

    public function write(ExportJob $job): string
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->exportDirectory);

        $filePath = rtrim($this->exportDirectory, '/') . '/' . $job->getType()->value . '_' . $job->getId() . '_' . (new \DateTimeImmutable())->format('YmdHis') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues($this->headers($job->getType())));

        foreach ($this->rows($job) as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $filePath;
    }

    /**
     * @return list<string>
     */
    private function headers(ExportType $type): array
    {
        return match ($type) {
            ExportType::COMPANIES => ['ID', 'Nombre', 'Email', 'Tipo de Documento', 'Número de Documento', 'Teléfono', 'Fecha de Creación'],
            ExportType::USERS => ['ID', 'Nombre', 'Apellido', 'Email', 'Rol', 'Empresa'],
        };
    }

    /**
     * @return iterable<list<scalar>>
     */
    private function rows(ExportJob $job): iterable
    {
        return match ($job->getType()) {
            ExportType::COMPANIES => $this->companyRows($job),
            ExportType::USERS => $this->userRows($job),
        };
    }

    private function companyRows(ExportJob $job): iterable
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

    private function userRows(ExportJob $job): iterable
    {
        $filters = UserExportFilters::fromArray($job->getFilters());

        foreach ($this->userRepository->iterateExportRowsByFilters($job->getRequestedBy(), $filters->name, $filters->role, $filters->documentNumber) as $user) {
            yield [
                $user['id'],
                $user['firstname'],
                $user['lastname'],
                $user['email'],
                $user['role']->value,
                $user['companyName'],
            ];
        }
    }
}
