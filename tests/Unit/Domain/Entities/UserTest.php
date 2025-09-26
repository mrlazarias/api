<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\UserId;
use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_can_create_user(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertEquals('João Silva', $user->getName());
        $this->assertEquals('joao@example.com', $user->getEmail()->toString());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isVerified());
        $this->assertEquals(['user'], $user->getRoles());
        $this->assertInstanceOf(Chronos::class, $user->getCreatedAt());
        $this->assertInstanceOf(Chronos::class, $user->getUpdatedAt());
    }

    public function test_can_verify_password(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertTrue($user->verifyPassword('password123'));
        $this->assertFalse($user->verifyPassword('senha-errada'));
    }

    public function test_can_update_name(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        $originalUpdatedAt = $user->getUpdatedAt();
        
        // Aguardar um pouco para garantir diferença no timestamp
        usleep(1000);
        
        $user->updateName('João Silva Santos');
        
        $this->assertEquals('João Silva Santos', $user->getName());
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function test_can_update_email(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        $user->verify(); // Verificar primeiro
        
        $this->assertTrue($user->isVerified());
        
        $user->updateEmail('joao.novo@example.com');
        
        $this->assertEquals('joao.novo@example.com', $user->getEmail()->toString());
        $this->assertFalse($user->isVerified()); // Deve resetar verificação
        $this->assertNull($user->getEmailVerifiedAt());
    }

    public function test_can_update_password(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        $originalHash = $user->getPasswordHash();
        
        $user->updatePassword('novasenha456');
        
        $this->assertNotEquals($originalHash, $user->getPasswordHash());
        $this->assertTrue($user->verifyPassword('novasenha456'));
        $this->assertFalse($user->verifyPassword('password123'));
    }

    public function test_can_activate_and_deactivate(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertTrue($user->isActive());
        
        $user->deactivate();
        $this->assertFalse($user->isActive());
        
        $user->activate();
        $this->assertTrue($user->isActive());
    }

    public function test_can_verify_email(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertFalse($user->isVerified());
        $this->assertNull($user->getEmailVerifiedAt());
        
        $user->verify();
        
        $this->assertTrue($user->isVerified());
        $this->assertInstanceOf(Chronos::class, $user->getEmailVerifiedAt());
    }

    public function test_can_manage_roles(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertEquals(['user'], $user->getRoles());
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        
        $user->addRole('admin');
        $this->assertTrue($user->hasRole('admin'));
        $this->assertEquals(['user', 'admin'], $user->getRoles());
        
        $user->removeRole('user');
        $this->assertFalse($user->hasRole('user'));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertEquals(['admin'], $user->getRoles());
    }

    public function test_cannot_add_duplicate_role(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        
        $this->assertEquals(['user'], $user->getRoles());
        
        $user->addRole('user'); // Tentar adicionar role duplicada
        
        $this->assertEquals(['user'], $user->getRoles()); // Deve continuar igual
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $user = User::create('João Silva', 'joao@example.com', 'password123');
        $array = $user->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('is_verified', $array);
        $this->assertArrayHasKey('email_verified_at', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('roles', $array);
        
        $this->assertEquals('João Silva', $array['name']);
        $this->assertEquals('joao@example.com', $array['email']);
        $this->assertTrue($array['is_active']);
        $this->assertFalse($array['is_verified']);
        $this->assertEquals(['user'], $array['roles']);
    }
}
