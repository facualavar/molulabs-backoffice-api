<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Enum\ExportType;
use App\Tests\Scenario\AdminExportJobScenario;
use Doctrine\ORM\EntityManagerInterface;

final class PersistedTestObjects
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    private function persistAndFlushEntities(array $entities = []): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<array{name: string, documentNumber?: string}> $specs
     * @return list<Company>
     */
    public function companies(array $specs): array
    {
        $companies = array_map(
            fn (array $spec) => TestObjects::getCompany($spec['name'], $spec['documentNumber'] ?? null),
            $specs,
        );

        $this->persistAndFlushEntities($companies);

        return $companies;
    }

    /**
     * @param list<array{firstname?: string, company?: Company, documentNumber?: string}> $specs
     * @return list<User>
     */
    public function users(array $specs): array
    {
        $entities = [];
        $users = [];

        foreach ($specs as $spec) {
            $company = $spec['company'] ?? null;

            if (!$company) {
                $company = TestObjects::getCompany();
                $entities[] = $company;
            }

            $user = TestObjects::getUser($company, $spec['firstname'] ?? 'Regular', $spec['documentNumber'] ?? null);
            $entities[] = $user;
            $users[] = $user;
        }

        $this->persistAndFlushEntities($entities);

        return $users;
    }

    public function adminWithOwnExportJob(ExportType $type = ExportType::USERS): AdminExportJobScenario
    {
        $company = TestObjects::getCompany();
        $admin = TestObjects::getAdminUser($company);
        $exportJob = TestObjects::getExportJob($admin, $type);

        $this->persistAndFlushEntities([$company, $admin, $exportJob]);

        return new AdminExportJobScenario($admin, $exportJob);
    }

    public function adminWithAnotherUsersExportJob(): AdminExportJobScenario
    {
        $adminCompany = TestObjects::getCompany();
        $admin = TestObjects::getAdminUser($adminCompany);

        $otherCompany = TestObjects::getCompany();
        $otherUser = TestObjects::getUser($otherCompany);

        $exportJob = TestObjects::getExportJob($otherUser);

        $this->persistAndFlushEntities([$adminCompany, $admin, $otherCompany, $otherUser, $exportJob]);

        return new AdminExportJobScenario($admin, $exportJob);
    }

    public function adminWithExportJobAndCompanyMate(ExportType $type = ExportType::USERS): AdminExportJobScenario
    {
        $company = TestObjects::getCompany();
        $admin = TestObjects::getAdminUser($company);
        $mate = TestObjects::getUser($company, 'Wanda');
        $exportJob = TestObjects::getExportJob($admin, $type);

        $this->persistAndFlushEntities([$company, $admin, $mate, $exportJob]);

        return new AdminExportJobScenario($admin, $exportJob);
    }

    /**
     * @return list<User>
     */
    public function adminWithSuperAdminCompanyMate(): array
    {
        $company = TestObjects::getCompany();
        $admin = TestObjects::getAdminUser($company);
        $superAdmin = TestObjects::getSuperAdminUser($company);

        $this->persistAndFlushEntities([$company, $admin, $superAdmin]);

        return [$admin, $superAdmin];
    }

    /**
     * @return array{0: User, 1: User, 2: ExportJob}
     */
    public function adminWithSuperAdminCompanyMateAndExportJob(ExportType $type = ExportType::USERS): array
    {
        $company = TestObjects::getCompany();
        $admin = TestObjects::getAdminUser($company);
        $superAdmin = TestObjects::getSuperAdminUser($company);
        $exportJob = TestObjects::getExportJob($admin, $type);

        $this->persistAndFlushEntities([$company, $admin, $superAdmin, $exportJob]);

        return [$admin, $superAdmin, $exportJob];
    }

    /**
     * @return list<User>
     */
    public function twoSuperAdmins(): array
    {
        $firstCompany = TestObjects::getCompany();
        $first = TestObjects::getSuperAdminUser($firstCompany);

        $secondCompany = TestObjects::getCompany();
        $second = TestObjects::getSuperAdminUser($secondCompany);

        $this->persistAndFlushEntities([$firstCompany, $first, $secondCompany, $second]);

        return [$first, $second];
    }

    public function getAdminUser(): User
    {
        $company = TestObjects::getCompany();
        $user = TestObjects::getAdminUser($company);

        $this->persistAndFlushEntities([$company, $user]);
        return $user;
    }

    public function getSuperAdminUser(): User
    {
        $company = TestObjects::getCompany('SuperAdmin HQ');
        $user = TestObjects::getSuperAdminUser($company);

        $this->persistAndFlushEntities([$company, $user]);
        return $user;
    }

    public function getUser(string $firstname = 'Regular', ?Company $company = null, ?string $documentNumber = null): User
    {
        $entities = [];

        if (!$company) {
            $company = TestObjects::getCompany();
            $entities[] = $company;
        }

        $user = TestObjects::getUser($company, $firstname, $documentNumber);
        $entities[] = $user;

        $this->persistAndFlushEntities($entities);
        return $user;
    }

    public function getCompany(string $name = 'Acme', ?string $documentNumber = null): Company
    {
        $company = TestObjects::getCompany($name, $documentNumber);

        $this->persistAndFlushEntities([$company]);
        return $company;
    }
}
