<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->getId()->toString()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->toString()] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        return array_slice(array_values($this->users), $offset, $limit);
    }

    public function delete(UserId $id): void
    {
        unset($this->users[$id->toString()]);
    }

    public function exists(UserId $id): bool
    {
        return isset($this->users[$id->toString()]);
    }

    public function emailExists(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function count(): int
    {
        return count($this->users);
    }
}

