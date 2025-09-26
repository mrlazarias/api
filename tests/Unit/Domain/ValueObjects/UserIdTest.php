<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\DomainException;
use App\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    public function test_can_create_valid_user_id(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId = new UserId($validUuid);
        
        $this->assertEquals($validUuid, $userId->toString());
    }

    public function test_can_generate_new_user_id(): void
    {
        $userId = UserId::generate();
        
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $userId->toString()
        );
    }

    public function test_can_create_from_string(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId = UserId::fromString($validUuid);
        
        $this->assertEquals($validUuid, $userId->toString());
    }

    public function test_throws_exception_for_invalid_uuid(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid user ID format');
        
        new UserId('invalid-uuid');
    }

    public function test_can_compare_user_ids(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId1 = new UserId($uuid);
        $userId2 = new UserId($uuid);
        $userId3 = UserId::generate();
        
        $this->assertTrue($userId1->equals($userId2));
        $this->assertFalse($userId1->equals($userId3));
    }

    public function test_can_convert_to_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId = new UserId($uuid);
        
        $this->assertEquals($uuid, (string) $userId);
    }
}
