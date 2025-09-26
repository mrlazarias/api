<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exceptions\DomainException;
use App\Domain\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Throwable;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }
    }

    private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        $statusCode = 500;
        $error = [
            'error' => 'Internal Server Error',
            'message' => 'An unexpected error occurred',
        ];

        // Log the exception
        $this->logger->error('Exception caught', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request' => [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
            ],
        ]);

        // Handle specific exceptions
        if ($exception instanceof ValidationException) {
            $statusCode = 422;
            $error = [
                'error' => 'Validation Error',
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ];
        } elseif ($exception instanceof DomainException) {
            $statusCode = $exception->getCode() ?: 400;
            $error = [
                'error' => 'Domain Error',
                'message' => $exception->getMessage(),
            ];
        }

        // In development, include more details
        if ($_ENV['APP_DEBUG'] === 'true') {
            $error['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        $response->getBody()->write(json_encode($error, JSON_PRETTY_PRINT));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}

