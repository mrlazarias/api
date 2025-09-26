<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

final class ProtectedRoutesTest extends TestCase
{
    private function getValidToken(): string
    {
        $loginRequest = $this->createJsonRequest('POST', '/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'minhasenha123'
        ]);
        $loginResponse = $this->makeRequest($loginRequest);
        $loginData = $this->getJsonResponseData($loginResponse);
        
        return $loginData['access_token'];
    }

    public function test_can_access_profile_with_valid_token(): void
    {
        $token = $this->getValidToken();
        
        $request = $this->createRequest('GET', '/api/v1/protected/profile', [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 200);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('joao@example.com', $data['email']);
        $this->assertTrue($data['is_active']);
    }

    public function test_cannot_access_profile_without_token(): void
    {
        $request = $this->createRequest('GET', '/api/v1/protected/profile');
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Missing or invalid authorization header');
    }

    public function test_cannot_access_profile_with_invalid_token(): void
    {
        $request = $this->createRequest('GET', '/api/v1/protected/profile', [
            'Authorization' => 'Bearer token-invalido'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Invalid or expired token');
    }

    public function test_cannot_access_profile_with_malformed_authorization_header(): void
    {
        $request = $this->createRequest('GET', '/api/v1/protected/profile', [
            'Authorization' => 'InvalidFormat token-here'
        ]);
        $response = $this->makeRequest($request);
        
        $this->assertUnauthorized($response, 'Missing or invalid authorization header');
    }

    public function test_can_update_profile_with_valid_token(): void
    {
        $token = $this->getValidToken();
        
        $updateData = [
            'name' => 'João Silva Santos',
            'email' => 'joao.santos@example.com'
        ];
        
        $request = $this->createJsonRequest('PUT', '/api/v1/protected/profile', $updateData, [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response = $this->makeRequest($request);
        
        $data = $this->assertJsonResponse($response, 200, [
            'message' => 'Profile updated successfully'
        ]);
        
        $this->assertArrayHasKey('user', $data);
    }
}
