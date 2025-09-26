<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Http\Middleware\CorsMiddleware;
use App\Infrastructure\Http\Middleware\ErrorHandlerMiddleware;
use App\Infrastructure\Http\Middleware\JsonBodyParserMiddleware;
use App\Infrastructure\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Logging\LoggerFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\App;

final class Application
{
    private App $app;

    private function __construct()
    {
        $this->app = AppFactory::create();
        $this->configureMiddleware();
        $this->configureRoutes();
    }

    public static function create(): self
    {
        return new self();
    }

    public function run(): void
    {
        $this->app->run();
    }

    private function configureMiddleware(): void
    {
        // Error handler middleware (should be first)
        $this->app->add(new ErrorHandlerMiddleware(LoggerFactory::create()));
        
        // CORS middleware
        $this->app->add(new CorsMiddleware());
        
        // Rate limiting middleware
        $this->app->add(new RateLimitMiddleware());
        
        // JSON body parser middleware
        $this->app->add(new JsonBodyParserMiddleware());
        
        // Built-in routing middleware
        $this->app->addRoutingMiddleware();
    }

    private function configureRoutes(): void
    {
        // Health check endpoint
        $this->app->get('/health', function (Request $request, Response $response) {
            $data = [
                'status' => 'ok',
                'timestamp' => date('c'),
                'version' => '1.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
            ];
            
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // API routes
        $this->app->group('/api/v1', function () {
            // Authentication routes
            $this->group('/auth', function () {
                $this->post('/register', '\App\Presentation\Controllers\AuthController:register');
                $this->post('/login', '\App\Presentation\Controllers\AuthController:login');
                $this->post('/refresh', '\App\Presentation\Controllers\AuthController:refresh');
                $this->post('/logout', '\App\Presentation\Controllers\AuthController:logout');
                $this->post('/forgot-password', '\App\Presentation\Controllers\AuthController:forgotPassword');
                $this->post('/reset-password', '\App\Presentation\Controllers\AuthController:resetPassword');
            });

            // User routes
            $this->group('/users', function () {
                $this->get('', '\App\Presentation\Controllers\UserController:index');
                $this->get('/{id}', '\App\Presentation\Controllers\UserController:show');
                $this->put('/{id}', '\App\Presentation\Controllers\UserController:update');
                $this->delete('/{id}', '\App\Presentation\Controllers\UserController:delete');
                $this->get('/{id}/profile', '\App\Presentation\Controllers\UserController:profile');
            });

            // Protected routes example
            $this->group('/protected', function () {
                $this->get('/profile', '\App\Presentation\Controllers\ProfileController:show');
                $this->put('/profile', '\App\Presentation\Controllers\ProfileController:update');
            })->add('\App\Presentation\Middleware\AuthMiddleware');
        });

        // Catch all route for API 404
        $this->app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/api/{routes:.+}', 
            function (Request $request, Response $response) {
                $data = [
                    'error' => 'Not Found',
                    'message' => 'The requested endpoint was not found',
                    'code' => 404,
                ];
                
                $response->getBody()->write(json_encode($data));
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
        );
    }
}

