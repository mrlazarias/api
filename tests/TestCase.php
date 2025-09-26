<?php

declare(strict_types=1);

namespace Tests;

use App\Infrastructure\Http\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;
    protected ServerRequestFactory $requestFactory;
    protected StreamFactory $streamFactory;
    protected UriFactory $uriFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['JWT_SECRET'] = 'test-secret-key-256-bits-long-for-testing-only';
        $_ENV['JWT_EXPIRY'] = '3600';
        $_ENV['RATE_LIMIT_REQUESTS'] = '1000';
        $_ENV['LOG_LEVEL'] = 'error';
        
        $this->app = Application::create();
        $this->requestFactory = new ServerRequestFactory();
        $this->streamFactory = new StreamFactory();
        $this->uriFactory = new UriFactory();
    }

    protected function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        ?string $body = null
    ): ServerRequestInterface {
        $request = $this->requestFactory->createServerRequest($method, $uri);
        
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        
        if ($body !== null) {
            $stream = $this->streamFactory->createStream($body);
            $request = $request->withBody($stream);
        }
        
        return $request;
    }

    protected function createJsonRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): ServerRequestInterface {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json';
        
        return $this->createRequest($method, $uri, $headers, $json);
    }

    protected function makeRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->getApp()->handle($request);
    }

    protected function getJsonResponseData(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function assertJsonResponse(
        ResponseInterface $response,
        int $expectedStatus = 200,
        ?array $expectedData = null
    ): array {
        $this->assertEquals($expectedStatus, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $data = $this->getJsonResponseData($response);
        
        if ($expectedData !== null) {
            foreach ($expectedData as $key => $value) {
                $this->assertArrayHasKey($key, $data);
                if (!is_array($value)) {
                    $this->assertEquals($value, $data[$key]);
                }
            }
        }
        
        return $data;
    }

    protected function assertValidationError(
        ResponseInterface $response,
        array $expectedErrors = []
    ): void {
        $data = $this->assertJsonResponse($response, 422);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation Error', $data['error']);
        $this->assertArrayHasKey('errors', $data);
        
        foreach ($expectedErrors as $field => $message) {
            $this->assertArrayHasKey($field, $data['errors']);
            if ($message !== null) {
                $this->assertStringContainsString($message, $data['errors'][$field]);
            }
        }
    }

    protected function assertUnauthorized(ResponseInterface $response, string $expectedMessage = null): void
    {
        $data = $this->assertJsonResponse($response, 401);
        
        $this->assertArrayHasKey('error', $data);
        
        if ($expectedMessage !== null) {
            $this->assertStringContainsString($expectedMessage, $data['message']);
        }
    }
}
