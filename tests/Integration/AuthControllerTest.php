<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class AuthControllerTest extends TestCase
{
    public function test_can_register_user(): void
    {
        // This would be a full integration test
        // with the actual HTTP stack
        $this->markTestIncomplete('Integration test to be implemented');
    }

    public function test_can_login_user(): void
    {
        $this->markTestIncomplete('Integration test to be implemented');
    }

    public function test_can_refresh_token(): void
    {
        $this->markTestIncomplete('Integration test to be implemented');
    }
}
