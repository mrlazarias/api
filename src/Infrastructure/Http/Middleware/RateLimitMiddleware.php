<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Cache\CacheManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private const DEFAULT_REQUESTS = 100;
    private const DEFAULT_WINDOW = 3600; // 1 hour

    public function __construct(
        private readonly CacheManager $cache
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = $this->getClientIdentifier($request);
        $key = "rate_limit:{$identifier}";

        $maxRequests = (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? self::DEFAULT_REQUESTS);
        $window = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? self::DEFAULT_WINDOW);

        $current = $this->cache->get($key, 0);

        if ($current >= $maxRequests) {
            $response = new Response();
            $error = [
                'error' => 'Rate Limit Exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $window,
            ];

            $response->getBody()->write(json_encode($error));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Limit', (string) $maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('X-RateLimit-Reset', (string) (time() + $window))
                ->withHeader('Retry-After', (string) $window);
        }

        // Increment counter
        $this->cache->set($key, $current + 1, $window);

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) ($maxRequests - $current - 1))
            ->withHeader('X-RateLimit-Reset', (string) (time() + $window));
    }

    private function getClientIdentifier(ServerRequestInterface $request): string
    {
        // Try to get user ID from JWT token if authenticated
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            // Extract user ID from token (simplified)
            // In real implementation, you'd decode the JWT
            return 'user:' . md5($authHeader);
        }

        // Fallback to IP address
        $serverParams = $request->getServerParams();
        $ip = $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['HTTP_X_REAL_IP']
            ?? $serverParams['REMOTE_ADDR']
            ?? 'unknown';

        return 'ip:' . $ip;
    }
}
