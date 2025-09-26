<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Entities\User;
use App\Domain\Exceptions\DomainException;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Infrastructure\Cache\CacheManager;
use App\Infrastructure\Cache\FileCacheManager;
use App\Infrastructure\Security\JwtManager;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtManager $jwtManager,
        private readonly CacheManager|FileCacheManager $cache
    ) {}

    public function register(string $name, string $email, string $password): User
    {
        $emailVo = new Email($email);

        // Check if email already exists
        if ($this->userRepository->emailExists($emailVo)) {
            throw new DomainException('Email already registered', 409);
        }

        $user = User::create($name, $email, $password);
        $this->userRepository->save($user);

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $emailVo = new Email($email);
        $user = $this->userRepository->findByEmail($emailVo);

        if (!$user || !$user->verifyPassword($password)) {
            throw new DomainException('Invalid credentials', 401);
        }

        if (!$user->isActive()) {
            throw new DomainException('Account is deactivated', 401);
        }

        $payload = [
            'user_id' => $user->getId()->toString(),
            'email' => $user->getEmail()->toString(),
            'roles' => $user->getRoles(),
        ];

        $accessToken = $this->jwtManager->generateToken($payload);
        $refreshToken = $this->jwtManager->generateRefreshToken($payload);

        // Cache user data for faster access
        $this->cache->set(
            "user:{$user->getId()->toString()}",
            $user->toArray(),
            3600
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600),
            'user' => $user->toArray(),
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->jwtManager->refreshToken($refreshToken);
    }

    public function validateToken(string $token): array
    {
        return $this->jwtManager->validateToken($token);
    }

    public function getUserFromToken(string $token): ?User
    {
        try {
            $payload = $this->jwtManager->validateToken($token);
            $userId = $payload['user_id'] ?? null;

            if (!$userId) {
                return null;
            }

            // Try to get from cache first
            $cachedUser = $this->cache->get("user:{$userId}");
            if ($cachedUser) {
                // Reconstruct User object from cached data
                return $this->reconstructUserFromArray($cachedUser);
            }

            // Fallback to repository
            return $this->userRepository->findById(new \App\Domain\ValueObjects\UserId($userId));

        } catch (DomainException) {
            return null;
        }
    }

    private function reconstructUserFromArray(array $data): User
    {
        return new User(
            new \App\Domain\ValueObjects\UserId($data['id']),
            $data['name'],
            new Email($data['email']),
            '', // Password hash not needed for reconstruction
            $data['is_active'],
            $data['is_verified'],
            $data['email_verified_at'] ? \Cake\Chronos\Chronos::parse($data['email_verified_at']) : null,
            \Cake\Chronos\Chronos::parse($data['created_at']),
            \Cake\Chronos\Chronos::parse($data['updated_at']),
            $data['roles']
        );
    }
}

