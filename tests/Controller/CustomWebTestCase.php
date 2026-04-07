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

    private function getResponseContent()
    {
        return json_decode($this->getResponseFromClient()->getContent(), true);
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