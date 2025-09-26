<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Security;

use App\Domain\Exceptions\DomainException;
use App\Infrastructure\Security\JwtManager;
use PHPUnit\Framework\TestCase;

final class JwtManagerTest extends TestCase
{
    private JwtManager $jwtManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $_ENV['JWT_SECRET'] = 'test-secret-key-256-bits-long-for-testing-only';
        $_ENV['JWT_ALGORITHM'] = 'HS256';
        $_ENV['JWT_EXPIRY'] = '3600';
        $_ENV['JWT_REFRESH_EXPIRY'] = '86400';
        $_ENV['APP_URL'] = 'http://localhost:8000';
        
        $this->jwtManager = new JwtManager();
    }

    public function test_can_generate_access_token(): void
    {
        $payload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['user']
        ];
        
        $token = $this->jwtManager->generateToken($payload);
        
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $token);
    }

    public function test_can_generate_refresh_token(): void
    {
        $payload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['user']
        ];
        
        $token = $this->jwtManager->generateRefreshToken($payload);
        
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $token);
    }

    public function test_can_validate_valid_token(): void
    {
        $originalPayload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['user']
        ];
        
        $token = $this->jwtManager->generateToken($originalPayload);
        $decodedPayload = $this->jwtManager->validateToken($token);
        
        $this->assertEquals($originalPayload['user_id'], $decodedPayload['user_id']);
        $this->assertEquals($originalPayload['email'], $decodedPayload['email']);
        $this->assertEquals($originalPayload['roles'], $decodedPayload['roles']);
        
        // Verificar campos automáticos
        $this->assertArrayHasKey('iat', $decodedPayload);
        $this->assertArrayHasKey('exp', $decodedPayload);
        $this->assertArrayHasKey('iss', $decodedPayload);
        $this->assertArrayHasKey('jti', $decodedPayload);
    }

    public function test_throws_exception_for_invalid_token(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid token');
        $this->expectExceptionCode(401);
        
        $this->jwtManager->validateToken('token-invalido');
    }

    public function test_throws_exception_for_malformed_token(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionCode(401);
        
        $this->jwtManager->validateToken('formato.jwt.invalido');
    }

    public function test_can_refresh_valid_refresh_token(): void
    {
        $payload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['user']
        ];
        
        $refreshToken = $this->jwtManager->generateRefreshToken($payload);
        $newTokens = $this->jwtManager->refreshToken($refreshToken);
        
        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertArrayHasKey('refresh_token', $newTokens);
        $this->assertArrayHasKey('token_type', $newTokens);
        $this->assertArrayHasKey('expires_in', $newTokens);
        
        $this->assertEquals('Bearer', $newTokens['token_type']);
        $this->assertEquals(3600, $newTokens['expires_in']);
    }

    public function test_cannot_refresh_access_token(): void
    {
        $payload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['user']
        ];
        
        $accessToken = $this->jwtManager->generateToken($payload);
        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid refresh token');
        
        $this->jwtManager->refreshToken($accessToken);
    }

    public function test_get_payload_returns_token_data(): void
    {
        $originalPayload = [
            'user_id' => '123',
            'email' => 'test@example.com',
            'roles' => ['admin', 'user']
        ];
        
        $token = $this->jwtManager->generateToken($originalPayload);
        $payload = $this->jwtManager->getPayload($token);
        
        $this->assertEquals($originalPayload['user_id'], $payload['user_id']);
        $this->assertEquals($originalPayload['email'], $payload['email']);
        $this->assertEquals($originalPayload['roles'], $payload['roles']);
    }

    public function test_tokens_have_different_jti(): void
    {
        $payload = ['user_id' => '123'];
        
        $token1 = $this->jwtManager->generateToken($payload);
        $token2 = $this->jwtManager->generateToken($payload);
        
        $payload1 = $this->jwtManager->validateToken($token1);
        $payload2 = $this->jwtManager->validateToken($token2);
        
        // JTI deve ser único para cada token
        $this->assertNotEquals($payload1['jti'], $payload2['jti']);
    }
}
