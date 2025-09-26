<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services;

use App\Application\Services\AuthService;
use App\Domain\Entities\User;
use App\Domain\Exceptions\DomainException;
use App\Domain\ValueObjects\Email;
use App\Infrastructure\Cache\FileCacheManager;
use App\Infrastructure\Persistence\InMemoryUserRepository;
use App\Infrastructure\Security\JwtManager;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private InMemoryUserRepository $userRepository;
    private JwtManager $jwtManager;
    private FileCacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        $_ENV['JWT_SECRET'] = 'test-secret-key-256-bits-long-for-testing-only';
        $_ENV['JWT_EXPIRY'] = '3600';
        $_ENV['JWT_REFRESH_EXPIRY'] = '86400';
        
        $this->userRepository = new InMemoryUserRepository();
        $this->jwtManager = new JwtManager();
        $this->cache = new FileCacheManager();
        
        $this->authService = new AuthService(
            $this->userRepository,
            $this->jwtManager,
            $this->cache
        );
    }

    public function test_can_register_new_user(): void
    {
        $user = $this->authService->register(
            'Maria Silva',
            'maria@example.com',
            'senhasegura123'
        );
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Maria Silva', $user->getName());
        $this->assertEquals('maria@example.com', $user->getEmail()->toString());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isVerified());
    }

    public function test_cannot_register_user_with_existing_email(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Email already registered');
        $this->expectExceptionCode(409);
        
        // Tentar registrar com email que já existe (usuário padrão)
        $this->authService->register(
            'Outro Usuário',
            'joao@example.com',
            'outrasenha123'
        );
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $tokens = $this->authService->login('joao@example.com', 'minhasenha123');
        
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('token_type', $tokens);
        $this->assertArrayHasKey('expires_in', $tokens);
        $this->assertArrayHasKey('user', $tokens);
        
        $this->assertEquals('Bearer', $tokens['token_type']);
        $this->assertEquals(3600, $tokens['expires_in']);
        
        // Verificar se são JWTs válidos
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $tokens['access_token']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+$/', $tokens['refresh_token']);
    }

    public function test_cannot_login_with_invalid_email(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(401);
        
        $this->authService->login('email-inexistente@example.com', 'qualquersenha');
    }

    public function test_cannot_login_with_invalid_password(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(401);
        
        $this->authService->login('joao@example.com', 'senha-errada');
    }

    public function test_can_validate_token(): void
    {
        $tokens = $this->authService->login('joao@example.com', 'minhasenha123');
        $payload = $this->authService->validateToken($tokens['access_token']);
        
        $this->assertArrayHasKey('user_id', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('roles', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        
        $this->assertEquals('joao@example.com', $payload['email']);
        $this->assertEquals(['user'], $payload['roles']);
    }

    public function test_can_refresh_token(): void
    {
        $originalTokens = $this->authService->login('joao@example.com', 'minhasenha123');
        $newTokens = $this->authService->refreshToken($originalTokens['refresh_token']);
        
        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertArrayHasKey('refresh_token', $newTokens);
        $this->assertEquals('Bearer', $newTokens['token_type']);
        
        // Tokens devem ser diferentes
        $this->assertNotEquals($originalTokens['access_token'], $newTokens['access_token']);
        $this->assertNotEquals($originalTokens['refresh_token'], $newTokens['refresh_token']);
    }

    public function test_cannot_refresh_with_access_token(): void
    {
        $tokens = $this->authService->login('joao@example.com', 'minhasenha123');
        
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid refresh token');
        
        // Tentar usar access token para refresh (deve falhar)
        $this->authService->refreshToken($tokens['access_token']);
    }

    public function test_can_get_user_from_token(): void
    {
        $tokens = $this->authService->login('joao@example.com', 'minhasenha123');
        $user = $this->authService->getUserFromToken($tokens['access_token']);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('joao@example.com', $user->getEmail()->toString());
        $this->assertTrue($user->isActive());
    }

    public function test_returns_null_for_invalid_token(): void
    {
        $user = $this->authService->getUserFromToken('token-invalido');
        
        $this->assertNull($user);
    }
}
