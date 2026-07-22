<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class CustomWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client;

    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    protected function postRequest(string $uri, array $data = [], array $headers = []): void
    {
        $headers = $this->addHeaderContentType($headers);

        $this->client->request('POST', $uri, [], [], $headers, json_encode($data));
    }

    protected function getRequest(string $uri, array $headers = []): void
    {
        $this->client->request('GET', $uri, [], [], $headers);
    }

    protected function putRequest(string $uri, array $data = [], array $headers = []): void
    {
        $headers = $this->addHeaderContentType($headers);

        $this->client->request('PUT', $uri, [], [], $headers, json_encode($data));
    }

    protected function deleteRequest(string $uri, array $headers = []): void
    {
        $this->client->request('DELETE', $uri, [], [], $headers);
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

    protected static function assertResponseIsCreated(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    protected static function assertResponseIsNoContent(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    protected static function assertResponseIsOk(): void
    {
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
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