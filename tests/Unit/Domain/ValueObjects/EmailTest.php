<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\Exceptions\DomainException;
use App\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = new Email('test@example.com');
        
        $this->assertEquals('test@example.com', $email->toString());
    }

    public function test_normalizes_email_case(): void
    {
        $email = new Email('TEST@EXAMPLE.COM');
        
        $this->assertEquals('test@example.com', $email->toString());
    }

    public function test_trims_whitespace(): void
    {
        $email = new Email('  test@example.com  ');
        
        $this->assertEquals('test@example.com', $email->toString());
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        new Email('invalid-email');
    }

    public function test_can_get_domain(): void
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('example.com', $email->getDomain());
    }

    public function test_can_get_local_part(): void
    {
        $email = new Email('user@example.com');
        
        $this->assertEquals('user', $email->getLocalPart());
    }

    public function test_can_compare_emails(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }
}
