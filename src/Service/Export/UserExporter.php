<?php

namespace App\Service\Export;

use App\Dto\User\UserExportFilters;
use App\Entity\ExportJob;
use App\Enum\ExportType;
use App\Repository\UserRepository;

final class UserExporter implements Exporter
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function type(): ExportType
    {
        return ExportType::USERS;
    }

    public function headers(): array
    {
        return ['ID', 'Nombre', 'Apellido', 'Email', 'Rol', 'Empresa'];
    }

    public function rows(ExportJob $job): iterable
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
