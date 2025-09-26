<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

final class MiddlewareTest extends TestCase
{
    public function test_cors_headers_are_present(): void
    {
        $request = $this->createRequest('GET', '/health');
        $response = $this->makeRequest($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verificar headers CORS básicos
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Headers'));
    }

    public function test_cors_headers_work_with_different_origins(): void
    {
        $request = $this->createRequest('GET', '/health', [
            'Origin' => 'http://localhost:3000'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Headers'));
        
        // Verificar se origin é permitido (configurado como * no CORS)
        $allowedOrigin = $response->getHeaderLine('Access-Control-Allow-Origin');
        $this->assertTrue(in_array($allowedOrigin, ['*', 'http://localhost:3000']));
    }

    public function test_json_body_parser_processes_json_requests(): void
    {
        $jsonData = ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'password123'];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/register', $jsonData);
        $response = $this->makeRequest($request);
        
        // Se o JSON foi processado corretamente, deve retornar 201 (sucesso) ou 409 (email duplicado)
        $this->assertTrue(in_array($response->getStatusCode(), [201, 409]));
    }

    public function test_rate_limiting_headers_are_present(): void
    {
        $request = $this->createRequest('GET', '/health');
        $response = $this->makeRequest($request);
        
        // Verificar se headers de rate limiting estão presentes
        $this->assertTrue($response->hasHeader('X-RateLimit-Limit'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Reset'));
        
        $limit = $response->getHeaderLine('X-RateLimit-Limit');
        $remaining = $response->getHeaderLine('X-RateLimit-Remaining');
        
        $this->assertIsNumeric($limit);
        $this->assertIsNumeric($remaining);
        $this->assertGreaterThan(0, (int) $limit);
    }

    public function test_error_handler_returns_json_for_invalid_api_routes(): void
    {
        $request = $this->createRequest('GET', '/api/v1/rota-inexistente');
        $response = $this->makeRequest($request);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $data = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Not Found', $data['error']);
    }

    public function test_authentication_middleware_validates_jwt_format(): void
    {
        // Token com formato inválido
        $request = $this->createRequest('GET', '/api/v1/protected/profile', [
            'Authorization' => 'Bearer formato.jwt.invalido'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response);
    }

    public function test_authentication_middleware_requires_bearer_prefix(): void
    {
        // Token sem prefixo Bearer
        $request = $this->createRequest('GET', '/api/v1/protected/profile', [
            'Authorization' => 'token-sem-bearer'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Missing or invalid authorization header');
    }
}
