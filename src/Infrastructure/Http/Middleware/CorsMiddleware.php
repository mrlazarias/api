<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');
        $allowedMethods = $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
        $allowedHeaders = $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With';
        
        $origin = $request->getHeaderLine('Origin');
        
        // Check if origin is allowed
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        return $response
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '3600');
    }
}

