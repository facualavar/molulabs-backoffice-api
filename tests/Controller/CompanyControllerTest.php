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

    public function testListingCompaniesReturnsSuccessfulResponse(): void
    {
        $this->getRequest('/api/companies');

        self::assertResponseIsOk();
    }

    public function testListingCompaniesFilteredByNameReturnsMatchingCompanies(): void
    {
        $matchingCompany = $this->persistedTestObjects->getCompany('Acme Corp');
        $this->persistedTestObjects->getCompany('Globex');

        $this->getRequest('/api/companies?name=Acme');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($matchingCompany->getId(), $content['data'][0]['id']);
    }

    public function testListingCompaniesFilteredByNonMatchingNameReturnsEmptyList(): void
    {
        $this->persistedTestObjects->getCompany('Acme Corp');

        $this->getRequest('/api/companies?name=Nonexistent');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content['data']);
    }

    public function testListingCompaniesRespectsLimit(): void
    {
        $this->persistedTestObjects->getCompany('Company A');
        $this->persistedTestObjects->getCompany('Company B');
        $this->persistedTestObjects->getCompany('Company C');

        $this->getRequest('/api/companies?limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(2, $content['data']);
    }

    public function testListingCompaniesRespectsPage(): void
    {
        $this->persistedTestObjects->getCompany('Company A');
        $this->persistedTestObjects->getCompany('Company B');
        $third = $this->persistedTestObjects->getCompany('Company C');

        $this->getRequest('/api/companies?page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content['data']);
        self::assertSame($third->getId(), $content['data'][0]['id']);
    }

    public function testListingCompaniesReturnsPaginationMeta(): void
    {
        $this->persistedTestObjects->getCompany('Company A');
        $this->persistedTestObjects->getCompany('Company B');
        $this->persistedTestObjects->getCompany('Company C');

        $this->getRequest('/api/companies?page=2&limit=2');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame(2, $content['meta']['page']);
        self::assertSame(2, $content['meta']['limit']);
        self::assertSame(3, $content['meta']['total']);
        self::assertSame(2, $content['meta']['totalPages']);
    }

    public function testAutocompletingCompaniesWithoutNameReturnsNotFound(): void
    {
        $this->getRequest('/api/companies/autocomplete');

        self::assertResponseIsNotFound();
    }

    public function testAutocompletingCompaniesFilteredByNameReturnsMatchingCompanies(): void
    {
        $matchingCompany = $this->persistedTestObjects->getCompany('Acme Corp');
        $this->persistedTestObjects->getCompany('Globex');

        $this->getRequest('/api/companies/autocomplete?name=Acme');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(1, $content);
        self::assertSame($matchingCompany->getId(), $content[0]['id']);
        self::assertSame($matchingCompany->getName(), $content[0]['name']);
    }

    public function testAutocompletingCompaniesFilteredByNonMatchingNameReturnsEmptyList(): void
    {
        $this->persistedTestObjects->getCompany('Acme Corp');

        $this->getRequest('/api/companies/autocomplete?name=Nonexistent');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(0, $content);
    }

    public function testAutocompletingCompaniesIsCappedAtFiveResults(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->persistedTestObjects->getCompany('Company ' . $i);
        }

        $this->getRequest('/api/companies/autocomplete?name=Company');

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertCount(5, $content);
    }

    public function testShowingExistingCompanyReturnsItsData(): void
    {
        $company = $this->persistedTestObjects->getCompany();

        $this->getRequest('/api/companies/' . $company->getId());

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame($company->getName(), $content['name']);
        self::assertSame($company->getEmail(), $content['email']);
    }

    public function testCreatingCompanyWithValidDataReturnsCreatedCompany(): void
    {
        $this->postRequest('/api/companies', [
            'name' => 'New Company',
            'email' => 'new@company.com',
        ]);

        self::assertResponseIsCreated();

        $content = $this->getResponseContent();
        self::assertSame('New Company', $content['name']);
        self::assertSame('new@company.com', $content['email']);
    }

    public function testUpdatingExistingCompanyReturnsUpdatedData(): void
    {
        $company = $this->persistedTestObjects->getCompany();

        $this->putRequest('/api/companies/' . $company->getId(), [
            'name' => 'Updated Name',
            'email' => 'updated@company.com',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testUpdatingOwnCompanyAsLoggedInUserReturnsUpdatedData(): void
    {
        $admin = $this->persistedTestObjects->getAdminUser();
        $company = $admin->getCompany();

        $this->loginAs($admin);

        $this->putRequest('/api/companies/' . $company->getId(), [
            'name' => 'Updated Name',
            'email' => 'updated@company.com',
        ]);

        self::assertResponseIsOk();

        $content = $this->getResponseContent();
        self::assertSame('Updated Name', $content['name']);
        self::assertSame('updated@company.com', $content['email']);
    }

    public function testDeletingExistingCompanyReturnsNoContent(): void
    {
        $company = $this->persistedTestObjects->getCompany();

        $this->deleteRequest('/api/companies/' . $company->getId());

        self::assertResponseIsNoContent();
    }
}
