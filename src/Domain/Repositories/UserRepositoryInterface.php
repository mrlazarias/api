<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    
    public function findById(UserId $id): ?User;
    
    public function findByEmail(Email $email): ?User;
    
    public function findAll(int $limit = 50, int $offset = 0): array;
    
    public function delete(UserId $id): void;
    
    public function exists(UserId $id): bool;
    
    public function emailExists(Email $email): bool;
    
    public function count(): int;
}

