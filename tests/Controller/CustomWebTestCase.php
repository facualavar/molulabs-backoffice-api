<?php

namespace App\Tests\Controller;

use App\Entity\ExportJob;
use App\Entity\User;
use App\Repository\ExportJobRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class CustomWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client;

    private ?string $token = null;

    protected function tearDown(): void
    {
        $this->client = null;
        $this->token = null;
        parent::tearDown();
    }

    protected function postRequest(string $uri, array $data = [], array $headers = []): void
    {
        $headers = $this->addAuthorization($this->addHeaderContentType($headers));

        $this->client->request('POST', $uri, [], [], $headers, json_encode($data));
    }

    protected function getRequest(string $uri, array $headers = []): void
    {
        $this->client->request('GET', $uri, [], [], $this->addAuthorization($headers));
    }

    protected function putRequest(string $uri, array $data = [], array $headers = []): void
    {
        $headers = $this->addAuthorization($this->addHeaderContentType($headers));

        $this->client->request('PUT', $uri, [], [], $headers, json_encode($data));
    }

    protected function deleteRequest(string $uri, array $headers = []): void
    {
        $this->client->request('DELETE', $uri, [], [], $this->addAuthorization($headers));
    }

    protected function patchRequest(string $uri, array $data = [], array $headers = []): void
    {
        $headers = $this->addAuthorization($this->addHeaderContentType($headers));

        $this->client->request('PATCH', $uri, [], [], $headers, json_encode($data));
    }

    /**
     * Mints the JWT the api firewall expects. Unlike KernelBrowser::loginUser(), which only
     * survives one request on a stateless firewall, the token is sent on every later request.
     */
    protected function loginAs(User $user): void
    {
        $this->token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    protected function logout(): void
    {
        $this->token = null;
    }

    protected function encryptPassword(User $user): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
    }

    private function addAuthorization(array $headers): array
    {
        if (!$this->token) {
            return $headers;
        }

        return array_merge($headers, [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
        ]);
    }

    private function addHeaderContentType(array $headers): array
    {
        return array_merge($headers, [
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    private function getResponseFromClient(): Response
    {
        return $this->client->getResponse();
    }

    protected function getResponseContent()
    {
        return json_decode($this->getResponseFromClient()->getContent(), true);
    }

    /**
     * @return array<string, string> the message of each field reported in the error response
     */
    protected function getFieldErrorMessages(): array
    {
        return array_column($this->getResponseContent()['errors'], 'message', 'field');
    }

    /**
     * The export endpoints only answer with the job; its stored filters are read back from here.
     */
    protected function findExportJob(int $id): ExportJob
    {
        return static::getContainer()->get(ExportJobRepository::class)->find($id);
    }

    protected static function assertResponseIsCreated(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    protected static function assertResponseIsAccepted(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    protected static function assertResponseIsNoContent(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    protected static function assertResponseIsOk(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    protected static function assertResponseIsNotFound(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    protected static function assertResponseIsUnauthorized(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    protected static function assertResponseIsForbidden(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    protected static function assertResponseIsBadRequest(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    protected static function assertResponseIsConflict(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    protected function assertResponseUnauthorizedWithMessage(string $message): void
    {
        $this->assertResponseError(Response::HTTP_UNAUTHORIZED, $message);
    }

    private function assertResponseError(int $code, string $message): void
    {
        $expectedResponse = [
            'code' => $code,
            'message' => $message
        ];

        $this->assertEquals($code, $this->getResponseFromClient()->getStatusCode(), $this->getResponseFromClient());
        $this->assertEquals($expectedResponse, $this->getResponseContent());
    }
}