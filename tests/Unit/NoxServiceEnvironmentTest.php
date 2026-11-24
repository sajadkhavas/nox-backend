<?php

namespace Tests\Unit;

use App\Support\NoxServiceEnvironment;
use Dotenv\Dotenv;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NoxServiceEnvironmentTest extends TestCase
{
    public function test_it_preserves_unrelated_database_and_application_keys(): void
    {
        $old = "APP_KEY=base64:keep\nDB_DATABASE=nox\nSMS_PROVIDER=disabled\n";
        $new = NoxServiceEnvironment::update($old, ['SMS_PROVIDER' => 'kavenegar', 'KAVENEGAR_API_KEY' => 'dummy-only']);
        $parsed = Dotenv::parse($new);
        self::assertSame('base64:keep', $parsed['APP_KEY']);
        self::assertSame('nox', $parsed['DB_DATABASE']);
        self::assertSame('kavenegar', $parsed['SMS_PROVIDER']);
        self::assertSame('dummy-only', $parsed['KAVENEGAR_API_KEY']);
    }

    public function test_duplicate_existing_entries_are_all_replaced_and_repeat_runs_are_stable(): void
    {
        $updated = NoxServiceEnvironment::update("SMS_PROVIDER=disabled\nSMS_PROVIDER=testing\n", ['SMS_PROVIDER' => 'kavenegar']);
        self::assertStringNotContainsString('testing', $updated);
        self::assertStringNotContainsString('disabled', $updated);
        self::assertSame($updated, NoxServiceEnvironment::update($updated, ['SMS_PROVIDER' => 'kavenegar']));
    }

    public function test_newlines_cannot_inject_another_setting(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NoxServiceEnvironment::update("APP_KEY=keep\n", ['KAVENEGAR_API_KEY' => "value\nAPP_KEY=changed"]);
    }
}
