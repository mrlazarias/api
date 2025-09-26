<?php

declare(strict_types=1);

namespace App\Presentation\Traits;

use Psr\Http\Message\ResponseInterface;

trait JsonResponseTrait
{
    private function writeJson(ResponseInterface $response, array $data, int $statusCode = 200): ResponseInterface
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
