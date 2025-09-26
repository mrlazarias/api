<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Services\AuthService;
use App\Domain\Exceptions\DomainException;
use App\Domain\Exceptions\ValidationException;
use App\Infrastructure\Cache\CacheFactory;
use App\Infrastructure\Security\JwtManager;
use App\Infrastructure\Persistence\InMemoryUserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

final class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        // In a real application, these would be injected via DI container
        $userRepository = new InMemoryUserRepository();
        $jwtManager = new JwtManager();
        $cacheManager = CacheFactory::create();
        
        $this->authService = new AuthService($userRepository, $jwtManager, $cacheManager);
    }

    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            $this->validateRegistrationData($data);

            $user = $this->authService->register(
                $data['name'],
                $data['email'],
                $data['password']
            );

            $result = [
                'message' => 'User registered successfully',
                'user' => $user->toArray(),
            ];

            $response->getBody()->write(json_encode($result));
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');

        } catch (ValidationException $e) {
            $error = [
                'error' => 'Validation Error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            $this->validateLoginData($data);

            $tokens = $this->authService->login($data['email'], $data['password']);

            $response->getBody()->write(json_encode($tokens));
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');

        } catch (ValidationException $e) {
            $error = [
                'error' => 'Validation Error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        } catch (DomainException $e) {
            $error = [
                'error' => 'Authentication Error',
                'message' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function refresh(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['refresh_token'])) {
                throw new ValidationException('Refresh token is required');
            }

            $tokens = $this->authService->refreshToken($data['refresh_token']);

            $response->getBody()->write(json_encode($tokens));
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');

        } catch (DomainException $e) {
            $error = [
                'error' => 'Token Error',
                'message' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        // In a real implementation, you would blacklist the token
        $result = [
            'message' => 'Logged out successfully',
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // In a real implementation, you would send a password reset email
        $result = [
            'message' => 'If the email exists, a password reset link has been sent',
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        // In a real implementation, you would validate the reset token and update password
        $result = [
            'message' => 'Password reset successfully',
        ];

        $response->getBody()->write(json_encode($result));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    private function validateRegistrationData(array $data): void
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (!v::stringType()->length(2, 100)->validate($data['name'])) {
            $errors['name'] = 'Name must be between 2 and 100 characters';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!v::email()->validate($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!v::stringType()->length(8, null)->validate($data['password'])) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
    }

    private function validateLoginData(array $data): void
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!v::email()->validate($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
    }
}

