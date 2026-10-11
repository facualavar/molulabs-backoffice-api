<?php

namespace App\Tests\Controller;

use App\Tests\PersistedTestObjects;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * What a plain user may reach. Ids that do not exist are fine for the allowed routes: they answer
 * 404 or 422, never 403. A forbidden route that takes an entity needs a real one, because Symfony
 * resolves the entity (404) before checking #[IsGranted].
 */
final class AccessControlTest extends CustomWebTestCase
{
    private ?PersistedTestObjects $persistedTestObjects;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->persistedTestObjects = new PersistedTestObjects($entityManager);
        $this->loginAs($this->persistedTestObjects->getUser());
    }

    protected function tearDown(): void
    {
        $this->persistedTestObjects = null;
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function plainUserEndpoints(): iterable
    {
        yield 'current user' => ['GET', '/api/me'];
        yield 'profile' => ['GET', '/api/profile'];
        yield 'profile update' => ['PUT', '/api/profile'];
        yield 'password change' => ['PUT', '/api/profile/password'];
        yield 'export status' => ['GET', '/api/exports/0'];
        yield 'export download' => ['GET', '/api/exports/0/download'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function adminOnlyEndpoints(): iterable
    {
        yield 'user list' => ['GET', '/api/users'];
        yield 'user create' => ['POST', '/api/users'];
        yield 'user export' => ['POST', '/api/users/export'];
        yield 'company list' => ['GET', '/api/companies'];
        yield 'own company' => ['GET', '/api/profile/company'];
    }

    #[DataProvider('plainUserEndpoints')]
    public function testPlainUserCanReachEndpoint(string $method, string $uri): void
    {
        $this->request($method, $uri);

        self::assertNotSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        self::assertNotSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[DataProvider('adminOnlyEndpoints')]
    public function testPlainUserIsForbiddenFromAdminOnlyEndpoint(string $method, string $uri): void
    {
        $this->request($method, $uri);

        self::assertResponseIsForbidden();
    }

    private function request(string $method, string $uri): void
    {
        match ($method) {
            'GET' => $this->getRequest($uri),
            'POST' => $this->postRequest($uri),
            'PUT' => $this->putRequest($uri),
            'PATCH' => $this->patchRequest($uri),
            'DELETE' => $this->deleteRequest($uri),
        };
    }
}
