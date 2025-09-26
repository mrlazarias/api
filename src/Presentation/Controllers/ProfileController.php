<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ProfileController
{
    public function show(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $error = ['error' => 'User not found in request'];
            $response->getBody()->write(json_encode($error));
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($user->toArray()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Implementation for profile update
        $result = [
            'message' => 'Profile updated successfully',
            'user' => $user->toArray(),
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

