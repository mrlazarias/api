<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use Cake\Chronos\Chronos;

final class User
{
    private UserId $id;
    private string $name;
    private Email $email;
    private string $passwordHash;
    private bool $isActive;
    private bool $isVerified;
    private ?Chronos $emailVerifiedAt;
    private Chronos $createdAt;
    private Chronos $updatedAt;
    private array $roles;

    public function __construct(
        UserId $id,
        string $name,
        Email $email,
        string $passwordHash,
        bool $isActive = true,
        bool $isVerified = false,
        ?Chronos $emailVerifiedAt = null,
        ?Chronos $createdAt = null,
        ?Chronos $updatedAt = null,
        array $roles = ['user']
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->isActive = $isActive;
        $this->isVerified = $isVerified;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->createdAt = $createdAt ?? Chronos::now();
        $this->updatedAt = $updatedAt ?? Chronos::now();
        $this->roles = $roles;
    }

    public static function create(
        string $name,
        string $email,
        string $password,
        array $roles = ['user']
    ): self {
        return new self(
            UserId::generate(),
            $name,
            new Email($email),
            password_hash($password, PASSWORD_ARGON2ID),
            roles: $roles
        );
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getEmailVerifiedAt(): ?Chronos
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): Chronos
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Chronos
    {
        return $this->updatedAt;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updateEmail(string $email): void
    {
        $this->email = new Email($email);
        $this->isVerified = false;
        $this->emailVerifiedAt = null;
        $this->touch();
    }

    public function updatePassword(string $password): void
    {
        $this->passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $this->touch();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->touch();
    }

    public function verify(): void
    {
        $this->isVerified = true;
        $this->emailVerifiedAt = Chronos::now();
        $this->touch();
    }

    public function addRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = $role;
            $this->touch();
        }
    }

    public function removeRole(string $role): void
    {
        $this->roles = array_values(array_filter($this->roles, fn ($r) => $r !== $role));
        $this->touch();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'name' => $this->name,
            'email' => $this->email->toString(),
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'email_verified_at' => $this->emailVerifiedAt?->toIso8601String(),
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
            'roles' => $this->roles,
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = Chronos::now();
    }
}
