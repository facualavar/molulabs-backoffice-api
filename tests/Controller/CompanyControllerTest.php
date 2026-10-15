<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;

final class CompanyControllerTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($entityManager);
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        parent::tearDown();
    }

    private function loginAsSuperAdmin(): void
    {
        $this->loginAs($this->persistedTestObjects->getSuperAdminUser());
    }

    private function companyPayload(string $name, string $email, array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'email' => $email,
            'documentType' => 'CUIT',
            'documentNumber' => '30-12345678-1',
            'phone' => '011-4444-5555',
        ], $overrides);
    }

    public function testListingCompaniesReturnsSuccessfulResponse(): void
    {
        $this->loginAsSuperAdmin();

        $this->getRequest('/api/companies');

        self::assertResponseIsOk();
    }

    public function testListingCompaniesAsRegularAdminReturnsForbidden(): void
    {
        $this->loginAs($this->persistedTestObjects->getAdminUser());

        $this->getRequest('/api/companies');

        self::assertResponseIsForbidden();
    }

    public function testListingCompaniesFilteredByNameReturnsMatchingCompanies(): void
    {
        $this->loginAsSuperAdmin();
        [$matchingCompany] = $this->persistedTestObjects->companies([
            ['name' => 'Acme Corp'],
            ['name' => 'Globex'],
        ]);

        $this->getRequest('/api/companies?name=Acme');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingCompany->getId(), $content['data'][0]['id']);
    }

    public function testListingCompaniesFilteredByNonMatchingNameReturnsEmptyList(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->getCompany('Acme Corp');

        $this->getRequest('/api/companies?name=Nonexistent');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content['data']);
    }

    public function testListingCompaniesFilteredByDocumentNumberReturnsMatchingCompanies(): void
    {
        $this->loginAsSuperAdmin();
        [$matchingCompany] = $this->persistedTestObjects->companies([
            ['name' => 'Acme Corp', 'documentNumber' => '20-11223344-0'],
            ['name' => 'Globex', 'documentNumber' => '30-98765432-1'],
        ]);

        $this->getRequest('/api/companies?documentNumber=11223344');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingCompany->getId(), $content['data'][0]['id']);
    }

    public function testListingCompaniesFilteredByNonMatchingDocumentNumberReturnsEmptyList(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->getCompany('Acme Corp', '30-12345678-1');

        $this->getRequest('/api/companies?documentNumber=00000000');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content['data']);
    }

    public function testListingCompaniesRespectsLimit(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->companies([
            ['name' => 'Company A'],
            ['name' => 'Company B'],
            ['name' => 'Company C'],
        ]);

        $this->getRequest('/api/companies?limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingCompaniesRespectsPage(): void
    {
        $this->loginAsSuperAdmin();
        [, , $third] = $this->persistedTestObjects->companies([
            ['name' => 'Company A'],
            ['name' => 'Company B'],
            ['name' => 'Company C'],
        ]);

        $this->getRequest('/api/companies?name=Company&page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($third->getId(), $content['data'][0]['id']);
    }

    public function testListingCompaniesReturnsPaginationMeta(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->companies([
            ['name' => 'Company A'],
            ['name' => 'Company B'],
            ['name' => 'Company C'],
        ]);

        $this->getRequest('/api/companies?name=Company&page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame(2, $content['meta']['page']);
        self::assertSame(2, $content['meta']['limit']);
        self::assertSame(3, $content['meta']['total']);
        self::assertSame(2, $content['meta']['totalPages']);
    }

    public function testExportingCompaniesReturnsAcceptedWithPendingJob(): void
    {
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/companies/export');

        self::assertResponseIsAccepted();

        $content = $this->getResponseContent();
        self::assertSame('companies', $content['type']);
        self::assertSame('pending', $content['status']);
    }

    public function testExportingCompaniesKeepsListFilters(): void
    {
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/companies/export?name=Acme&documentNumber=7123');

        self::assertResponseIsAccepted();
        self::assertSame(
            ['name' => 'Acme', 'documentNumber' => '7123'],
            $this->findExportJob($this->getResponseContent()['id'])->getFilters(),
        );
    }

    public function testExportingCompaniesAsRegularAdminReturnsForbidden(): void
    {
        $this->loginAs($this->persistedTestObjects->getAdminUser());

        $this->postRequest('/api/companies/export');

        self::assertResponseIsForbidden();
    }

    public function testAutocompletingCompaniesWithoutNameReturnsNotFound(): void
    {
        $this->loginAsSuperAdmin();

        $this->getRequest('/api/companies/autocomplete');

        self::assertResponseIsNotFound();
    }

    public function testAutocompletingCompaniesFilteredByNameReturnsMatchingCompanies(): void
    {
        $this->loginAsSuperAdmin();
        [$matchingCompany] = $this->persistedTestObjects->companies([
            ['name' => 'Acme Corp'],
            ['name' => 'Globex'],
        ]);

        $this->getRequest('/api/companies/autocomplete?name=Acme');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content);
        self::assertSame($matchingCompany->getId(), $content[0]['id']);
        self::assertSame($matchingCompany->getName(), $content[0]['name']);
    }

    public function testAutocompletingCompaniesFilteredByNonMatchingNameReturnsEmptyList(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->getCompany('Acme Corp');

        $this->getRequest('/api/companies/autocomplete?name=Nonexistent');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content);
    }

    public function testAutocompletingCompaniesIsCappedAtFiveResults(): void
    {
        $this->loginAsSuperAdmin();
        $this->persistedTestObjects->companies(
            array_map(static fn (int $i) => ['name' => 'Company ' . $i], range(1, 7)),
        );

        $this->getRequest('/api/companies/autocomplete?name=Company');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(5, $content);
    }

    public function testShowingExistingCompanyReturnsItsData(): void
    {
        $this->loginAsSuperAdmin();
        $company = $this->persistedTestObjects->getCompany();

        $this->getRequest('/api/companies/' . $company->getId());

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($company->getName(), $content['name']);
        self::assertSame($company->getEmail(), $content['email']);
        self::assertSame($company->getDocumentType()->value, $content['documentType']);
        self::assertSame($company->getDocumentNumber(), $content['documentNumber']);
        self::assertSame($company->getPhone(), $content['phone']);
    }

    public function testCreatingCompanyWithValidDataReturnsCreatedCompany(): void
    {
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/companies', $this->companyPayload('New Company', 'new@company.com'));

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame('New Company', $content['name']);
        self::assertSame('new@company.com', $content['email']);
        self::assertSame('CUIT', $content['documentType']);
        self::assertSame('30-12345678-1', $content['documentNumber']);
        self::assertSame('011-4444-5555', $content['phone']);
    }

    public function testCreatingCompanyWithDuplicateDocumentNumberReturnsConflict(): void
    {
        $this->loginAsSuperAdmin();
        $existingCompany = $this->persistedTestObjects->getCompany('Acme Corp', '30-12345678-1');

        $this->postRequest('/api/companies', $this->companyPayload('New Company', 'new@company.com', [
            'documentNumber' => $existingCompany->getDocumentNumber(),
        ]));

        self::assertResponseIsConflict();

        $content = $this->getResponseContent();
        self::assertSame('documentNumber', $content['errors'][0]['field']);
    }

    public function testCreatingCompanyWithDuplicateEmailReturnsConflict(): void
    {
        $this->loginAsSuperAdmin();
        $existingCompany = $this->persistedTestObjects->getCompany('Acme Corp');

        $this->postRequest('/api/companies', $this->companyPayload('New Company', $existingCompany->getEmail()));

        self::assertResponseIsConflict();

        $content = $this->getResponseContent();
        self::assertSame('email', $content['errors'][0]['field']);
    }

    public function testUpdatingExistingCompanyReturnsUpdatedData(): void
    {
        $this->loginAsSuperAdmin();
        $company = $this->persistedTestObjects->getCompany();

        $this->putRequest('/api/companies/' . $company->getId(), $this->companyPayload('Updated Name', 'updated@company.com', [
            'documentType' => 'DNI',
            'documentNumber' => '12345678',
            'phone' => '011-5555-4444',
        ]));

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame('updated@company.com', $content['email']);
        self::assertSame('DNI', $content['documentType']);
        self::assertSame('12345678', $content['documentNumber']);
    }

    public function testUpdatingCompanyKeepingItsEmailAndDocumentNumberReturnsUpdatedData(): void
    {
        $this->loginAsSuperAdmin();
        $company = $this->persistedTestObjects->getCompany();

        $this->putRequest('/api/companies/' . $company->getId(), $this->companyPayload('Updated Name', $company->getEmail(), [
            'documentType' => $company->getDocumentType()->value,
            'documentNumber' => $company->getDocumentNumber(),
        ]));

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame($company->getEmail(), $content['email']);
        self::assertSame($company->getDocumentNumber(), $content['documentNumber']);
    }

    public function testUpdatingOwnCompanyAsLoggedInSuperAdminReturnsUpdatedData(): void
    {
        $superAdmin = $this->persistedTestObjects->getSuperAdminUser();
        $company = $superAdmin->getCompany();

        $this->loginAs($superAdmin);

        $this->putRequest('/api/companies/' . $company->getId(), $this->companyPayload('Updated Name', 'updated@company.com'));

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testUpdatingCompanyAsRegularAdminReturnsForbidden(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $this->loginAs($admin);

        $this->putRequest('/api/companies/' . $admin->getCompany()->getId(), $this->companyPayload('Updated Name', 'updated@company.com'));

        self::assertResponseIsForbidden();
    }

    public function testDeletingExistingCompanyReturnsNoContent(): void
    {
        $this->loginAsSuperAdmin();
        $company = $this->persistedTestObjects->getCompany();

        $this->deleteRequest('/api/companies/' . $company->getId());

        self::assertResponseIsNoContent();
    }

    public function testDeletingCompanyWithUsersReturnsConflict(): void
    {
        $this->loginAsSuperAdmin();
        $user = $this->persistedTestObjects->getUser();

        $this->deleteRequest('/api/companies/' . $user->getCompany()->getId());

        self::assertResponseIsConflict();
    }

    public function testCreatingCompanyWithBlankFieldsReturnsTheValidationMessages(): void
    {
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/companies', $this->companyPayload('', '', ['documentNumber' => '', 'phone' => '']));

        self::assertSame([
            'name' => 'El nombre no puede estar vacío.',
            'email' => 'El email no puede estar vacío.',
            'documentNumber' => 'El número de documento no puede estar vacío.',
            'phone' => 'El teléfono no puede estar vacío.',
        ], $this->getFieldErrorMessages());
    }

    public function testCreatingCompanyWithTooLongFieldsReturnsTheLengthMessages(): void
    {
        $this->loginAsSuperAdmin();

        $this->postRequest('/api/companies', $this->companyPayload(str_repeat('a', 51), str_repeat('a', 90) . '@example.com', ['phone' => str_repeat('1', 21)]));

        self::assertSame([
            'name' => 'El nombre no puede tener más de 50 caracteres.',
            'email' => 'El email no puede tener más de 100 caracteres.',
            'phone' => 'El teléfono no puede tener más de 20 caracteres.',
        ], $this->getFieldErrorMessages());
    }
}
