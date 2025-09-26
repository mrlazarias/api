<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\DomainException;

final class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim(strtolower($value));

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Invalid email format');
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function getDomain(): string
    {
        $atPos = strpos($this->value, '@');

        return $atPos !== false ? substr($this->value, $atPos + 1) : '';
    }

    public function getLocalPart(): string
    {
        $atPos = strpos($this->value, '@');

        return $atPos !== false ? substr($this->value, 0, $atPos) : '';
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
