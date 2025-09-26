<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Domain\ValueObjects\UserId;
use App\Infrastructure\Persistence\InMemoryUserRepository;
use App\Presentation\Traits\JsonResponseTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UserController
{
    use JsonResponseTrait;

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
            'data' => array_map(fn ($user) => $user->toArray(), $users),
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
        ];

        return $this->writeJson($response, $result);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = new UserId($args['id']);
            $user = $this->userRepository->findById($userId);

            if (!$user) {
                $error = ['error' => 'User not found'];

                return $this->writeJson($response, $error, 404);
            }

            return $this->writeJson($response, $user->toArray());

        } catch (\Exception $e) {
            $error = ['error' => 'Invalid user ID format'];

            return $this->writeJson($response, $error, 400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        // Implementation for user update
        $result = ['message' => 'User update not implemented yet'];

        return $this->writeJson($response, $result);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        // Implementation for user deletion
        $result = ['message' => 'User deletion not implemented yet'];

        return $this->writeJson($response, $result);
    }

    public function profile(Request $request, Response $response, array $args): Response
    {
        // Implementation for user profile
        $result = ['message' => 'User profile not implemented yet'];

        return $this->writeJson($response, $result);
    }
}
