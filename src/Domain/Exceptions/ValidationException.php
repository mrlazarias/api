<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

class ValidationException extends DomainException
{
    private array $errors;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

