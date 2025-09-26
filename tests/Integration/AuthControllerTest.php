<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

final class AuthControllerTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $request = $this->createRequest('GET', '/health');
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 200, [
            'status' => 'ok',
            'version' => '1.0.0'
        ]);
        
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('environment', $data);
    }

    public function test_can_register_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao.teste@example.com',
            'password' => 'senhasegura123'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/register', $userData);
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 201, [
            'message' => 'User registered successfully'
        ]);
        
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals($userData['name'], $data['user']['name']);
        $this->assertEquals($userData['email'], $data['user']['email']);
        $this->assertTrue($data['user']['is_active']);
        $this->assertFalse($data['user']['is_verified']);
        $this->assertEquals(['user'], $data['user']['roles']);
    }

    public function test_cannot_register_user_with_invalid_data(): void
    {
        $invalidData = [
            'name' => '',
            'email' => 'email-invalido',
            'password' => '123'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/register', $invalidData);
        $response = $this->makeRequest($request);
        
        $this->assertValidationError($response, [
            'name' => 'required',
            'email' => 'Invalid email',
            'password' => 'at least 8'
        ]);
    }

    public function test_cannot_register_user_with_duplicate_email(): void
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com', // Email que já existe (usuário padrão)
            'password' => 'senhasegura123'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/register', $userData);
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 409);
        $this->assertStringContainsString('Email already registered', $data['message']);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $loginData = [
            'email' => 'joao@example.com',
            'password' => 'minhasenha123'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/login', $loginData);
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 200);
        
        // Verificar estrutura do token
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertArrayHasKey('user', $data);
        
        $this->assertEquals('Bearer', $data['token_type']);
        $this->assertEquals(3600, $data['expires_in']);
        
        // Verificar dados do usuário
        $this->assertEquals('joao@example.com', $data['user']['email']);
        $this->assertTrue($data['user']['is_active']);
        
        // Verificar se é um JWT válido (formato básico)
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $data['access_token']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $data['refresh_token']);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        $loginData = [
            'email' => 'joao@example.com',
            'password' => 'senha-errada'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/login', $loginData);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Invalid credentials');
    }

    public function test_cannot_login_with_invalid_email_format(): void
    {
        $loginData = [
            'email' => 'email-invalido',
            'password' => 'qualquersenha'
        ];
        
        $request = $this->createJsonRequest('POST', '/api/v1/auth/login', $loginData);
        $response = $this->makeRequest($request);
        
        $this->assertValidationError($response, [
            'email' => 'Invalid email'
        ]);
    }

    public function test_can_refresh_token(): void
    {
        // Primeiro fazer login para obter tokens
        $loginRequest = $this->createJsonRequest('POST', '/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'minhasenha123'
        ]);
        $loginResponse = $this->makeRequest($loginRequest);
        $loginData = $this->getJsonResponseData($loginResponse);
        
        // Usar refresh token
        $refreshRequest = $this->createJsonRequest('POST', '/api/v1/auth/refresh', [
            'refresh_token' => $loginData['refresh_token']
        ]);
        $refreshResponse = $this->makeRequest($refreshRequest);
        
        $data = $this->assertJsonResponse($refreshResponse, 200);
        
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertEquals('Bearer', $data['token_type']);
        
        // Verificar que os tokens são diferentes dos originais
        $this->assertNotEquals($loginData['access_token'], $data['access_token']);
        $this->assertNotEquals($loginData['refresh_token'], $data['refresh_token']);
    }

    public function test_cannot_refresh_with_invalid_token(): void
    {
        $request = $this->createJsonRequest('POST', '/api/v1/auth/refresh', [
            'refresh_token' => 'token-invalido'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Invalid token');
    }

    public function test_logout_returns_success_message(): void
    {
        // Fazer login primeiro
        $loginRequest = $this->createJsonRequest('POST', '/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'minhasenha123'
        ]);
        $loginResponse = $this->makeRequest($loginRequest);
        $loginData = $this->getJsonResponseData($loginResponse);
        
        // Fazer logout
        $logoutRequest = $this->createRequest('POST', '/api/v1/auth/logout', [
            'Authorization' => 'Bearer ' . $loginData['access_token']
        ]);
        $logoutResponse = $this->makeRequest($logoutRequest);
        
        $data = $this->assertJsonResponse($logoutResponse, 200, [
            'message' => 'Logged out successfully'
        ]);
    }

    public function test_api_404_for_invalid_endpoints(): void
    {
        $request = $this->createRequest('GET', '/api/v1/endpoint-inexistente');
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 404, [
            'error' => 'Not Found',
            'code' => 404
        ]);
        
        $this->assertStringContainsString('not found', $data['message']);
    }
}
