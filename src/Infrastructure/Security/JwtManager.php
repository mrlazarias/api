<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Exceptions\DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

final class JwtManager
{
    private string $secret;
    private string $algorithm;
    private int $expiry;
    private int $refreshExpiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? throw new DomainException('JWT_SECRET not configured');
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 3600);
        $this->refreshExpiry = (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 86400);
    }

    public function generateToken(array $payload): string
    {
        $now = time();
        
        $tokenPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->expiry,
            'iss' => $_ENV['APP_URL'] ?? 'localhost',
            'jti' => bin2hex(random_bytes(16)), // Unique token ID
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    public function generateRefreshToken(array $payload): string
    {
        $now = time();
        
        $tokenPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->refreshExpiry,
            'iss' => $_ENV['APP_URL'] ?? 'localhost',
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)),
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new DomainException('Token expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new DomainException('Invalid token signature', 401);
        } catch (\Exception $e) {
            throw new DomainException('Invalid token', 401);
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        $payload = $this->validateToken($refreshToken);
        
        if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
            throw new DomainException('Invalid refresh token', 401);
        }

        // Remove refresh-specific fields
        unset($payload['type'], $payload['iat'], $payload['exp'], $payload['jti']);

        return [
            'access_token' => $this->generateToken($payload),
            'refresh_token' => $this->generateRefreshToken($payload),
            'token_type' => 'Bearer',
            'expires_in' => $this->expiry,
        ];
    }

    public function getPayload(string $token): array
    {
        return $this->validateToken($token);
    }
}

