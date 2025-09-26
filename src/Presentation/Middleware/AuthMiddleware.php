<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Application\Services\AuthService;
use App\Infrastructure\Cache\CacheFactory;
use App\Infrastructure\Persistence\InMemoryUserRepository;
use App\Infrastructure\Security\JwtManager;
use App\Presentation\Traits\JsonResponseTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    use JsonResponseTrait;

    private AuthService $authService;

    public function __construct()
    {
        // In a real application, these would be injected via DI container
        $userRepository = new InMemoryUserRepository();
        $jwtManager = new JwtManager();
        $cacheManager = CacheFactory::create();

        $this->authService = new AuthService($userRepository, $jwtManager, $cacheManager);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse('Missing or invalid authorization header');
        }

        $token = substr($authHeader, 7);
        $user = $this->authService->getUserFromToken($token);

        if (!$user) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        if (!$user->isActive()) {
            return $this->unauthorizedResponse('Account is deactivated');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('user_id', $user->getId()->toString());

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $error = [
            'error' => 'Unauthorized',
            'message' => $message,
        ];

        return $this->writeJson($response, $error, 401);
    }
}
