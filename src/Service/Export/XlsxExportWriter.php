<?php

namespace App\Service\Export;

use App\Entity\ExportJob;
use App\Enum\ExportType;
use App\Enum\UserRole;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\Filesystem\Filesystem;

final class XlsxExportWriter
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
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
        $filters = $job->getFilters();
        $protectedCompanyId = $job->getRequestedBy()->getCompany()->getId();

        foreach ($this->companyRepository->iterateByFilters($filters['name'] ?? null, $filters['documentNumber'] ?? null) as $company) {
            yield [
                $company->getId(),
                $company->getName(),
                $company->getEmail(),
                $company->getDocumentType()->value,
                $company->getDocumentNumber(),
                $company->getPhone(),
                $company->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            if ($company->getId() !== $protectedCompanyId) {
                $this->entityManager->detach($company);
            }
        }
    }

    private function userRows(ExportJob $job): iterable
    {
        $filters = $job->getFilters();
        $name = $filters['name'] ?? null;
        $role = isset($filters['role']) ? UserRole::from($filters['role']) : null;
        $documentNumber = $filters['documentNumber'] ?? null;
        $requestedBy = $job->getRequestedBy();
        $protectedUserId = $requestedBy->getId();

        foreach ($this->userRepository->iterateByFilters($requestedBy, $name, $role, $documentNumber) as $user) {
            yield [
                $user->getId(),
                $user->getFirstname(),
                $user->getLastname(),
                $user->getEmail(),
                $user->getRole()->value,
                $user->getCompany()->getName(),
            ];

            if ($user->getId() !== $protectedUserId) {
                $this->entityManager->detach($user);
            }
        }
    }
}
