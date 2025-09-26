<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Persistence\InMemoryUserRepository;
use App\Domain\ValueObjects\UserId;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UserController
{
    private InMemoryUserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new InMemoryUserRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $limit = min((int) ($queryParams['limit'] ?? 50), 100);
        $offset = (int) ($queryParams['offset'] ?? 0);
        
        $users = $this->userRepository->findAll($limit, $offset);
        $total = $this->userRepository->count();
        
        $result = [
            'data' => array_map(fn($user) => $user->toArray(), $users),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
        ];
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = new UserId($args['id']);
            $user = $this->userRepository->findById($userId);
            
            if (!$user) {
                $error = ['error' => 'User not found'];
                $response->getBody()->write(json_encode($error));
                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }
            
            $response->getBody()->write(json_encode($user->toArray()));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $error = ['error' => 'Invalid user ID format'];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        // Implementation for user update
        $result = ['message' => 'User update not implemented yet'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        // Implementation for user deletion
        $result = ['message' => 'User deletion not implemented yet'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function profile(Request $request, Response $response, array $args): Response
    {
        // Implementation for user profile
        $result = ['message' => 'User profile not implemented yet'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

