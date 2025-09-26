<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Presentation\Traits\JsonResponseTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ProfileController
{
    use JsonResponseTrait;
    public function show(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $error = ['error' => 'User not found in request'];

            return $this->writeJson($response, $error, 500);
        }

        return $this->writeJson($response, $user->toArray());
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

        return $this->writeJson($response, $result);
    }
}
